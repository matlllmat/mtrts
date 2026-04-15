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
      </div>
      <h3 class="text-lg text-gray-700"><?= htmlspecialchars($ticket['title']) ?></h3>
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
  
    <?php if ($is_staff && $ticket['status'] !== 'closed' && $ticket['status'] !== 'cancelled'): ?>
    <!-- Staff Quick Actions -->
    <div class="bg-blue-50 rounded-xl border border-blue-100 p-4">
      <h3 class="font-bold text-blue-900 mb-3 text-sm">Update Status</h3>
      <form action="save.php" method="POST" class="space-y-3">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
        
        <select name="status" class="fsel" required>
          <?php foreach(['new'=>'New','assigned'=>'Assigned','in_progress'=>'In Progress','on_hold'=>'On Hold','resolved'=>'Resolved','closed'=>'Closed','cancelled'=>'Cancelled'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $ticket['status']==$k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
        
        <select name="assigned_to" class="fsel">
          <option value="">-- Assign Technician --</option>
          <?php foreach($assignables as $a): ?>
            <option value="<?= $a['user_id'] ?>" <?= $ticket['assigned_to']==$a['user_id'] ? 'selected':'' ?>><?= htmlspecialchars($a['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
        
        <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm transition">
          Save Status
        </button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Details Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Details
      </div>
      <div class="p-5">
        <dl class="space-y-3 text-sm">
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
        <div class="font-medium text-gray-900"><?= htmlspecialchars($ticket['requester_name']) ?></div>
        <div class="text-gray-600 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <a href="mailto:<?= htmlspecialchars($ticket['requester_email']) ?>" class="hover:underline"><?= htmlspecialchars($ticket['requester_email']) ?></a>
        </div>
        <?php if ($ticket['contact_number']): ?>
        <div class="text-gray-600 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <?= htmlspecialchars($ticket['contact_number']) ?>
        </div>
        <?php endif; ?>
        <?php if ($ticket['requester_dept']): ?>
          <div class="text-gray-600"><?= htmlspecialchars($ticket['requester_dept']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Asset Card -->
    <?php if ($ticket['asset_id']): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Asset Details
      </div>
      <div class="p-5 space-y-3 text-sm">
        <div>
          <span class="text-xs font-mono font-bold text-olfu-green bg-green-50 px-2 py-0.5 rounded border border-green-200"><?= htmlspecialchars($ticket['asset_tag']) ?></span>
        </div>
        <div>
          <dt class="text-gray-500 mb-0.5">Model</dt>
          <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['manufacturer'] . ' ' . $ticket['model']) ?></dd>
        </div>
        <div>
          <a href="<?= BASE_URL ?>modules/assets/view.php?id=<?= $ticket['asset_id'] ?>" class="text-olfu-green hover:underline font-semibold block mt-1">View Full Asset →</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

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
