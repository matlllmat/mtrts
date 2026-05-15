<!-- Header -->
<div class="mb-4">
  <a href="index.php" class="text-sm text-olfu-green hover:underline flex items-center gap-1 mb-2">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Back to Tickets
  </a>
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="flex items-center gap-3 mb-1">
        <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($ticket['ticket_number']) ?></h2>
        <?= ticket_status_badge($ticket['status']) ?>
        <?php if($ticket['is_event_support']): ?>
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
            ⚡ Urgent Event
          </span>
        <?php endif; ?>
        <?php if(($ticket['channel'] ?? '') === 'email'): ?>
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200" title="Submitted via email gateway">
            📧 Email
          </span>
        <?php endif; ?>
      </div>
      <h3 class="text-lg text-gray-700"><?= htmlspecialchars($ticket['title']) ?></h3>
      <?php if(($ticket['channel'] ?? '') === 'email'):
        $sender_name  = $ticket['external_name_from'] ?: ($ticket['requester_name'] ?? 'Unknown');
        $sender_email = $ticket['external_email_from'] ?: ($ticket['requester_email'] ?? '');
      ?>
        <p class="mt-2 text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-md px-3 py-2 inline-flex items-center gap-2">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6m-18 0v8a2 2 0 002 2h14a2 2 0 002-2V8m-18 0V6a2 2 0 012-2h14a2 2 0 012 2v2"/></svg>
          Received via email gateway — From <strong><?= htmlspecialchars($sender_name) ?></strong>
          <?php if ($sender_email): ?>&lt;<?= htmlspecialchars($sender_email) ?>&gt;<?php endif; ?>
          <?php if (!empty($ticket['external_email_from'])): ?>
            <span class="text-blue-500 ml-1 italic">(unregistered sender)</span>
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'cancelled'): ?>
        <a href="edit.php?id=<?= $ticket['ticket_id'] ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
          Edit Request
        </a>
        <?php if ($is_staff && empty($related_wos)): ?>
        <a href="<?= BASE_URL ?>modules/workorders/add.php?ticket_id=<?= $ticket['ticket_id'] ?>" class="px-4 py-2 bg-olfu-green hover:bg-olfu-green-md text-white rounded-lg text-sm font-semibold transition">
          Create Work Order
        </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'duplicate_voided'): ?>
    <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-lg flex items-center gap-3">
      <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <div>
        <h4 class="text-sm font-bold text-amber-800">Duplicate Submission Voided</h4>
        <p class="text-xs text-amber-700">A new request was submitted but detected as a duplicate. It has been voided and linked to this ticket for tracking.</p>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($original_ticket): ?>
    <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg flex items-center gap-3">
      <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <div>
        <h4 class="text-sm font-bold text-red-800">Voided: Duplicate Ticket</h4>
        <p class="text-xs text-red-700">This ticket has been marked as a duplicate of 
          <a href="view.php?id=<?= $original_ticket['ticket_id'] ?>" class="font-bold underline">#<?= htmlspecialchars($original_ticket['ticket_number']) ?></a>.
          Please refer to the original ticket for updates.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($duplicates)): ?>
    <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg flex flex-col gap-1">
      <div class="flex items-center gap-3">
        <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
        <h4 class="text-sm font-bold text-blue-800">Duplicate Requests (<?= count($duplicates) ?>)</h4>
      </div>
      <div class="pl-9 text-xs text-blue-700">
        The following submissions were identified as duplicates of this ticket:
        <div class="flex flex-wrap gap-2 mt-1">
          <?php foreach ($duplicates as $dup): ?>
            <a href="view.php?id=<?= $dup['ticket_id'] ?>" class="bg-blue-100 px-2 py-0.5 rounded border border-blue-200 hover:bg-blue-200 transition">
              #<?= htmlspecialchars($dup['ticket_number']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Main Column -->
  <div class="lg:col-span-2 space-y-6">
    <!-- Description -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 flex justify-between items-center">
        Description
      </div>
      <div class="p-5 text-gray-700 whitespace-pre-wrap font-sans"><?= htmlspecialchars($ticket['description'] ?: 'No description provided.') ?></div>
    </div>
    
    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900 bg-gray-50">
        Attachments (<?= count($attachments) ?>)
      </div>
      <div class="p-5">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <?php foreach ($attachments as $att): ?>
          <a href="<?= BASE_URL . htmlspecialchars($att['file_path']) ?>" target="_blank" class="block border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition">
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
              <span class="text-sm font-semibold text-olfu-green truncate" title="<?= htmlspecialchars($att['file_name']) ?>">
                <?= htmlspecialchars($att['file_name']) ?>
              </span>
            </div>
            <div class="text-xs text-gray-500"><?= $att['file_size_kb'] ?> KB</div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Comments / Notes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900 bg-gray-50">
        Updates & Comments
      </div>
      <div class="p-5 space-y-4">
        <?php if (empty($comments)): ?>
          <div class="text-center text-sm text-gray-400 py-4">No comments yet.</div>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="flex gap-3 <?= $c['is_internal'] ? 'p-3 bg-amber-50 rounded-lg border border-amber-100' : '' ?>">
              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 overflow-hidden">
                <svg class="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
              </div>
              <div class="flex-1">
                <div class="flex items-baseline gap-2 mb-1">
                  <span class="font-bold text-sm text-gray-900"><?= htmlspecialchars($c['user_name']) ?></span>
                  <span class="text-xs text-gray-500"><?= date('M j, Y h:i A', strtotime($c['created_at'])) ?></span>
                  <?php if ($c['is_internal']): ?>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-amber-700 bg-amber-200 px-1.5 py-0.5 rounded">Internal Note</span>
                  <?php endif; ?>
                </div>
                <div class="text-sm text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($c['comment_text']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'cancelled'): ?>
        <form action="save.php" method="POST" class="mt-4 pt-4 border-t border-gray-100">
          <input type="hidden" name="action" value="add_comment">
          <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
          <textarea name="comment_text" rows="3" required class="fin mb-2" placeholder="Write a comment or update..."></textarea>
          <div class="flex justify-between items-center">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
              <?php if ($is_staff): ?>
                <input type="checkbox" name="is_internal" value="1" class="text-olfu-green focus:ring-olfu-green rounded border-gray-300">
                Staff Note (hidden from requester)
              <?php endif; ?>
            </label>
            <button type="submit" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-lg text-sm transition">
              Add Comment
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar Column -->
  <div class="space-y-6">
  


    <?php if ($ticket_sla): ?>
    <!-- SLA Countdown Widget -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" id="sla-widget">
      <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
        <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          SLA Tracker
        </h3>
        <?php
        $fmt_mins = function(int $m): string {
            if ($m < 60) return $m . 'm';
            $h = intdiv($m, 60); $rem = $m % 60;
            return $h . 'h' . ($rem ? ' ' . $rem . 'm' : '');
        };
        ?>
        <button id="sla-policy-btn"
                onclick="toggleSlaPop(this)"
                class="text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-gray-100 hover:bg-gray-200 hover:text-[#1a5c2a] px-2 py-0.5 rounded transition-colors cursor-pointer flex items-center gap-1">
          <?= htmlspecialchars($ticket_sla['policy_name']) ?>
          <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
      </div>

      <!-- SLA Policy Popover (fixed so it clears overflow-hidden) -->
      <div id="sla-policy-pop" class="hidden fixed z-[200] w-80 bg-white border border-gray-200 rounded-2xl shadow-2xl text-left" style="top:0;right:0">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50 rounded-t-2xl">
          <div>
            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($ticket_sla['policy_name']) ?></p>
            <p class="text-[10px] text-gray-400 mt-0.5">Applied SLA Policy</p>
          </div>
          <?php if ($ticket_sla['uses_business_hours']): ?>
            <span class="text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg">Business Hours</span>
          <?php else: ?>
            <span class="text-[9px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 px-2 py-1 rounded-lg">24 / 7</span>
          <?php endif; ?>
        </div>

        <!-- Timeline limits -->
        <div class="px-4 py-3 space-y-2.5">
          <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Configured Time Limits</p>

          <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></div>
            <div class="flex-1 flex justify-between items-center">
              <span class="text-xs font-semibold text-gray-600">Response</span>
              <span class="text-xs font-bold text-gray-900 font-mono bg-gray-100 px-2 py-0.5 rounded"><?= $fmt_mins((int)$ticket_sla['response_minutes']) ?></span>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></div>
            <div class="flex-1 flex justify-between items-center">
              <span class="text-xs font-semibold text-gray-600">Diagnosis</span>
              <span class="text-xs font-bold text-gray-900 font-mono bg-gray-100 px-2 py-0.5 rounded"><?= $fmt_mins((int)$ticket_sla['diagnosis_minutes']) ?></span>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-green-500 shrink-0"></div>
            <div class="flex-1 flex justify-between items-center">
              <span class="text-xs font-semibold text-gray-600">Resolution</span>
              <span class="text-xs font-bold text-gray-900 font-mono bg-gray-100 px-2 py-0.5 rounded"><?= $fmt_mins((int)$ticket_sla['resolution_minutes']) ?></span>
            </div>
          </div>
        </div>

        <?php if ($ticket_sla['uses_business_hours']): ?>
        <!-- Business hours explanation -->
        <div class="mx-4 mb-4 bg-amber-50 border border-amber-200 rounded-xl px-3 py-3">
          <p class="text-[10px] font-bold text-amber-800 uppercase tracking-wider mb-1.5">Why is the deadline days away?</p>
          <p class="text-[11px] text-amber-700 leading-relaxed">
            The SLA clock only ticks during <strong>working hours</strong>. Nights, weekends, and holidays don't count — so a 4-hour response window can stretch over several calendar days.
          </p>
          <p class="text-[11px] text-amber-600 mt-1.5 font-medium">
            Example: A ticket filed Friday 4 PM with a 4h response = deadline Monday morning.
          </p>
        </div>
        <?php else: ?>
        <div class="mx-4 mb-4 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2.5">
          <p class="text-[11px] text-purple-700 leading-relaxed">
            This policy runs <strong>24/7</strong> — the SLA clock never stops, including nights, weekends, and holidays.
          </p>
        </div>
        <?php endif; ?>
      </div>

      <script>
      function toggleSlaPop(btn) {
        const pop = document.getElementById('sla-policy-pop');
        if (!pop.classList.contains('hidden')) { pop.classList.add('hidden'); return; }
        const r = btn.getBoundingClientRect();
        pop.style.top  = (r.bottom + 6) + 'px';
        pop.style.left = 'auto';
        pop.style.right = (window.innerWidth - r.right) + 'px';
        pop.classList.remove('hidden');
      }
      document.addEventListener('click', function(e) {
        const pop = document.getElementById('sla-policy-pop');
        const btn = document.getElementById('sla-policy-btn');
        if (pop && !pop.classList.contains('hidden') && !pop.contains(e.target) && !btn.contains(e.target)) {
          pop.classList.add('hidden');
        }
      });
      </script>
      <div class="p-4 space-y-3">

        <?php if ($ticket_sla['paused_at']): ?>
          <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold px-3 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            SLA Clock Paused
            <?php if ($ticket_sla['pause_reason']): ?>
              — <?= htmlspecialchars(ucwords(str_replace('_', ' ', $ticket_sla['pause_reason']))) ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php
        // Helper: compute remaining time or breach status for a stage
        $sla_stages = [
            ['label' => 'Response',  'due' => $ticket_sla['response_due'],  'actual' => $ticket_sla['responded_at'],  'breached' => $ticket_sla['is_response_breached']],
            ['label' => 'Diagnosis', 'due' => $ticket_sla['diagnosis_due'], 'actual' => $ticket_sla['diagnosed_at'],  'breached' => $ticket_sla['is_diagnosis_breached']],
            ['label' => 'Resolution','due' => $ticket_sla['resolution_due'],'actual' => $ticket_sla['resolved_at'],   'breached' => $ticket_sla['is_resolution_breached']],
        ];
        ?>

        <?php foreach ($sla_stages as $stage): ?>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?= $stage['label'] ?></p>
            <?php if ($stage['actual']): ?>
              <p class="text-[10px] text-gray-400 mt-0.5">Completed <?= date('M j, g:i A', strtotime($stage['actual'])) ?></p>
            <?php elseif ($stage['due']): ?>
              <p class="text-[10px] text-gray-400 mt-0.5">Due <?= date('M j, g:i A', strtotime($stage['due'])) ?></p>
            <?php endif; ?>
          </div>
          <div class="text-right">
            <?php if ($stage['actual']): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                Met
              </span>
            <?php elseif ($stage['breached']): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200 animate-pulse">
                ⚠ Breached
              </span>
            <?php elseif ($stage['due']): ?>
              <span class="sla-countdown text-xs font-mono font-bold text-gray-700" data-due="<?= htmlspecialchars($stage['due']) ?>" data-paused="<?= $ticket_sla['paused_at'] ? '1' : '0' ?>">
                --:--:--
              </span>
            <?php else: ?>
              <span class="text-[10px] text-gray-300 italic">N/A</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($ticket_sla['total_paused_minutes'] > 0): ?>
        <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
          <span>Total paused time</span>
          <span class="font-mono font-bold"><?php
            $pm = (int)$ticket_sla['total_paused_minutes'];
            echo ($pm >= 60 ? floor($pm/60).'h ' : '') . ($pm % 60) . 'm';
          ?></span>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <script>
    // Live SLA countdown timer
    (function() {
      function updateCountdowns() {
        document.querySelectorAll('.sla-countdown').forEach(el => {
          if (el.dataset.paused === '1') {
            el.textContent = 'PAUSED';
            el.classList.add('text-amber-600');
            return;
          }
          const due = new Date(el.dataset.due + ' UTC');
          const now = new Date();
          let diff = due - now;
          const isNegative = diff < 0;
          if (isNegative) diff = Math.abs(diff);

          const totalSec = Math.floor(diff / 1000);
          const d = Math.floor(totalSec / 86400);
          const h = Math.floor((totalSec % 86400) / 3600);
          const m = Math.floor((totalSec % 3600) / 60);
          const s = totalSec % 60;
          const pad = n => String(n).padStart(2, '0');

          let label;
          if (d > 0)       label = `${d}d ${pad(h)}h ${pad(m)}m ${pad(s)}s`;
          else if (h > 0)  label = `${pad(h)}h ${pad(m)}m ${pad(s)}s`;
          else             label = `${pad(m)}m ${pad(s)}s`;

          if (isNegative) {
            el.textContent = 'Overdue';
            el.classList.remove('text-gray-700', 'text-amber-600');
            el.classList.add('text-red-600', 'animate-pulse');
          } else if (d === 0 && h === 0 && m < 30) {
            el.textContent = label;
            el.classList.remove('text-gray-700', 'text-red-600');
            el.classList.add('text-amber-600');
          } else {
            el.textContent = label;
            el.classList.remove('text-red-600', 'text-amber-600', 'animate-pulse');
            el.classList.add('text-gray-700');
          }
        });
      }
      updateCountdowns();
      setInterval(updateCountdowns, 1000);
    })();
    </script>
    <?php endif; ?>

    <!-- Details Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Details
      </div>
      <div class="p-5">
        <dl class="space-y-3 text-sm">
          <?php if (!empty($ticket['assigned_to_name'])): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Assigned To</dt>
            <dd class="font-medium text-gray-900 font-bold text-olfu-green flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <?= htmlspecialchars($ticket['assigned_to_name']) ?>
            </dd>
          </div>
          <?php endif; ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Priority</dt>
            <dd><?= ticket_priority_badge($ticket['priority']) ?></dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Urgency / Impact</dt>
            <dd class="font-medium text-gray-900 capitalize"><?= $ticket['urgency'] ?> / <?= $ticket['impact'] ?></dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Category</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['category_name'] ?: 'None specified') ?></dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Location</dt>
            <dd class="font-medium text-gray-900">
              <?php if($ticket['location_id']): ?>
                <?= htmlspecialchars($ticket['building'] . ' - ' . $ticket['floor'] . ' - ' . $ticket['room']) ?>
              <?php else: ?>
                Pending
              <?php endif; ?>
            </dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Submitted</dt>
            <dd class="font-medium text-gray-900"><?= date('M j, Y h:i A', strtotime($ticket['created_at'])) ?> (via <?= ucfirst($ticket['channel']) ?>)</dd>
          </div>
        </dl>
      </div>
    </div>
    
    <!-- Requester Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Requester
      </div>
      <div class="p-5 space-y-2 text-sm">
        <?php 
          $display_name = !empty($ticket['requester_name']) ? $ticket['requester_name'] : (!empty($ticket['external_name_from']) ? $ticket['external_name_from'] : 'Unknown Sender');
          $display_email = !empty($ticket['requester_email']) ? $ticket['requester_email'] : (!empty($ticket['external_email_from']) ? $ticket['external_email_from'] : 'No email provided');
          $display_dept = !empty($ticket['requester_dept']) ? $ticket['requester_dept'] : (!empty($ticket['external_dept_from']) ? $ticket['external_dept_from'] : '');
        ?>
        <div class="font-medium text-gray-900"><?= htmlspecialchars($display_name) ?></div>
        <div class="text-gray-600 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <?php if ($display_email !== 'No email provided'): ?>
            <a href="mailto:<?= htmlspecialchars($display_email) ?>" class="hover:underline"><?= htmlspecialchars($display_email) ?></a>
          <?php else: ?>
            <span><?= htmlspecialchars($display_email) ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($ticket['contact_number'])): ?>
        <div class="text-gray-600 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <?= htmlspecialchars($ticket['contact_number']) ?>
        </div>
        <?php endif; ?>
        <?php if ($display_dept): ?>
          <div class="text-gray-600"><?= htmlspecialchars($display_dept) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Asset / Equipment Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Equipment Identification
      </div>
      <div class="p-5 space-y-3 text-sm">
        <?php if (!empty($ticket['asset_tag'])): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Reported Asset ID</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['asset_tag']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if ($ticket['asset_id']): ?>
          <div>
            <span class="text-xs font-mono font-bold text-olfu-green bg-green-50 px-2 py-0.5 rounded border border-green-200"><?= htmlspecialchars($ticket['asset_tag_linked'] ?? $ticket['asset_tag']) ?></span>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Linked Asset</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['manufacturer'] . ' ' . $ticket['asset_model']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if ($ticket['model']): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Reported Model</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['model']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if ($ticket['warranty_status']): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Warranty Status</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['warranty_status']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if ($ticket['asset_id']): ?>
          <div>
            <a href="<?= BASE_URL ?>modules/assets/view.php?id=<?= $ticket['asset_id'] ?>" class="text-olfu-green hover:underline font-semibold block mt-1">View Full Asset →</a>
          </div>
        <?php endif; ?>

        <?php if (!$ticket['asset_id'] && !$ticket['model'] && !$ticket['warranty_status']): ?>
          <div class="text-gray-400 italic">No specific asset or model identified.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dynamic Fields -->
    <?php if (!empty($dynamic_fields)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Additional Data
      </div>
      <div class="p-5">
        <dl class="space-y-3 text-sm">
          <?php foreach ($dynamic_fields as $k => $v): ?>
          <div>
            <dt class="text-gray-500 mb-0.5"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $k))) ?></dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($v) ?></dd>
          </div>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Related Work Orders -->
    <?php if (!empty($related_wos)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Related Work Orders
      </div>
      <div class="p-4 space-y-3">
        <?php foreach ($related_wos as $wo): ?>
          <a href="<?= BASE_URL ?>modules/workorders/view.php?id=<?= $wo['wo_id'] ?>" class="block p-3 rounded-lg border border-gray-100 hover:border-olfu-green hover:shadow-sm transition group">
            <div class="flex justify-between items-center mb-1">
              <span class="font-mono font-bold text-xs text-olfu-green group-hover:underline"><?= htmlspecialchars($wo['wo_number']) ?></span>
              <span class="text-[10px] uppercase font-bold text-gray-500"><?= htmlspecialchars($wo['status']) ?></span>
            </div>
            <div class="text-xs text-gray-600">
              <?= $wo['scheduled_start'] ? 'Scheduled: ' . date('M j', strtotime($wo['scheduled_start'])) : 'Unscheduled' ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
