<?php
// modules/workorders/_form.view.php — Shared form partial for add + edit.
// Variables expected: $is_edit, $wo, $errors, $old, $technicians, $tickets

$v = fn($k) => htmlspecialchars($old[$k] ?? $wo[$k] ?? '');
$e = fn($k) => isset($errors[$k]) ? 'fin-err' : '';
?>

<!-- Back + breadcrumb -->
<div class="flex items-center gap-2 mb-4">
  <a href="<?= $is_edit ? 'view.php?id=' . ($wo['wo_id'] ?? '') : 'index.php' ?>"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    <?= $is_edit ? 'Back to ' . htmlspecialchars($wo['wo_number'] ?? '') : 'Back to Work Orders' ?>
  </a>
</div>

<form method="POST" action="save.php" class="max-w-3xl">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>" />
  <?php if ($is_edit): ?>
    <input type="hidden" name="wo_id" value="<?= $wo['wo_id'] ?>" />
  <?php endif; ?>

  <!-- Page header card -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4">
    <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
      <span class="block w-0.5 h-5 bg-olfu-green rounded"></span>
      <?= $is_edit ? 'Edit Work Order — ' . htmlspecialchars($wo['wo_number']) : 'Create Work Order' ?>
    </h2>
    <p class="text-sm text-gray-400 mt-0.5">
      <?= $is_edit ? 'Update the work order details below.' : 'Fill in the details to create a new work order.' ?>
    </p>
  </div>

  <!-- Error banner -->
  <?php if ($errors): ?>
  <div class="wo-banner banner-warn mb-4">
    <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <span>Please correct the errors below.</span>
  </div>
  <?php endif; ?>

  <!-- Basic Info -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <div class="sdiv mb-4" style="padding-top:0">Basic Information</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Ticket -->
      <div>
        <label class="flbl">Linked Ticket</label>
        <select name="ticket_id" class="fsel <?= $e('ticket_id') ?>">
          <option value="">— No ticket (Direct WO) —</option>
          <?php foreach ($tickets as $tk): ?>
            <option value="<?= $tk['ticket_id'] ?>" <?= ($v('ticket_id') ?: '') == $tk['ticket_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($tk['ticket_number']) ?> — <?= htmlspecialchars(mb_strimwidth($tk['title'], 0, 40, '…')) ?>
              <?= $tk['asset_tag'] ? ' [' . htmlspecialchars($tk['asset_tag']) . ']' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($tickets)): ?>
          <p class="fhint">No open tickets available. You can create a direct work order.</p>
        <?php endif; ?>
        <?php if (isset($errors['ticket_id'])): ?><p class="ferr-msg"><?= $errors['ticket_id'] ?></p><?php endif; ?>
      </div>

      <!-- WO Type -->
      <div>
        <label class="flbl">Type <span class="text-red-400">*</span></label>
        <select name="wo_type" class="fsel <?= $e('wo_type') ?>" required>
          <?php foreach (['diagnosis'=>'Diagnosis','repair'=>'Repair','maintenance'=>'Maintenance','follow_up'=>'Follow-up'] as $k=>$label): ?>
            <option value="<?= $k ?>" <?= ($v('wo_type') ?: 'repair') === $k ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['wo_type'])): ?><p class="ferr-msg"><?= $errors['wo_type'] ?></p><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Assignment -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <div class="sdiv mb-4" style="padding-top:0">Assignment</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="flbl">Assign To</label>
        <select name="assigned_to" class="fsel">
          <option value="">— Unassigned —</option>
          <?php foreach ($technicians as $t): ?>
            <option value="<?= $t['user_id'] ?>" <?= ($v('assigned_to') ?: '') == $t['user_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="fhint">IT staff and technicians available for assignment.</p>
      </div>

      <div>
        <label class="flbl">RMA (Return Merchandise Authorization)</label>
        <div class="flex items-center gap-2 mt-2">
          <input type="checkbox" name="is_rma" value="1" id="is-rma"
                 class="w-4 h-4 rounded border-gray-300 text-olfu-green focus:ring-green-500"
                 <?= ($old['is_rma'] ?? $wo['is_rma'] ?? 0) ? 'checked' : '' ?> />
          <label for="is-rma" class="text-sm text-gray-600">This work order involves a warranty RMA</label>
        </div>
      </div>
    </div>
  </div>

  <!-- Schedule -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <div class="sdiv mb-4" style="padding-top:0">Schedule</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="flbl">Scheduled Start</label>
        <input type="datetime-local" name="scheduled_start" class="fin <?= $e('scheduled_start') ?>"
               value="<?= $v('scheduled_start') ? (new DateTime($v('scheduled_start')))->format('Y-m-d\TH:i') : '' ?>" />
      </div>
      <div>
        <label class="flbl">Scheduled End</label>
        <input type="datetime-local" name="scheduled_end" class="fin <?= $e('scheduled_end') ?>"
               value="<?= $v('scheduled_end') ? (new DateTime($v('scheduled_end')))->format('Y-m-d\TH:i') : '' ?>" />
        <?php if (isset($errors['scheduled_end'])): ?><p class="ferr-msg"><?= $errors['scheduled_end'] ?></p><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Status (edit only) -->
  <?php if ($is_edit): ?>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <div class="sdiv mb-4" style="padding-top:0">Status</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="flbl">Status <span class="text-red-400">*</span></label>
        <select name="status" id="wo-status" class="fsel" onchange="toggleHoldReason()">
          <?php foreach (['new'=>'New','assigned'=>'Assigned','scheduled'=>'Scheduled','in_progress'=>'In Progress','on_hold'=>'On Hold','resolved'=>'Resolved','closed'=>'Closed'] as $k=>$label): ?>
            <option value="<?= $k ?>" <?= ($v('status') ?: 'new') === $k ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="hold-reason-wrap" class="<?= ($v('status') ?: '') !== 'on_hold' ? 'hidden' : '' ?>">
        <label class="flbl">On Hold Reason</label>
        <select name="on_hold_reason" class="fsel <?= $e('on_hold_reason') ?>">
          <option value="">— Select reason —</option>
          <?php foreach (['waiting_parts'=>'Waiting for parts','waiting_vendor'=>'Waiting for vendor','waiting_access'=>'Waiting for access','other'=>'Other'] as $k=>$label): ?>
            <option value="<?= $k ?>" <?= ($v('on_hold_reason') ?: '') === $k ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['on_hold_reason'])): ?><p class="ferr-msg"><?= $errors['on_hold_reason'] ?></p><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Notes -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <div class="sdiv mb-4" style="padding-top:0">Notes</div>

    <div class="grid grid-cols-1 gap-4">
      <div>
        <label class="flbl">Work Notes</label>
        <textarea name="notes" rows="4" class="fin" placeholder="Initial instructions, observations, or notes for the technician…"><?= $v('notes') ?></textarea>
      </div>

      <?php if ($is_edit): ?>
      <div>
        <label class="flbl">Resolution Notes</label>
        <textarea name="resolution_notes" rows="3" class="fin" placeholder="Summary of what was done, parts replaced, outcome…"><?= $v('resolution_notes') ?></textarea>
        <p class="fhint">Fill this in when resolving or closing the work order.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Actions -->
  <div class="flex items-center justify-end gap-3">
    <a href="<?= $is_edit ? 'view.php?id=' . $wo['wo_id'] : 'index.php' ?>"
       class="text-sm font-medium text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
            class="inline-flex items-center gap-2 bg-olfu-green text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-olfu-green-md transition-colors">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
      </svg>
      <?= $is_edit ? 'Save Changes' : 'Create Work Order' ?>
    </button>
  </div>
</form>

<script>
function toggleHoldReason() {
  const wrap = document.getElementById('hold-reason-wrap');
  const sel  = document.getElementById('wo-status');
  if (wrap && sel) {
    wrap.classList.toggle('hidden', sel.value !== 'on_hold');
  }
}
</script>
