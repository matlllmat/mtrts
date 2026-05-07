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

<form method="POST" action="save.php" class="w-full">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>" />
  <?php if ($is_edit): ?>
    <input type="hidden" name="wo_id" value="<?= $wo['wo_id'] ?>" />
  <?php endif; ?>

  <!-- Page header card -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-6">
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
  <div class="wo-banner banner-warn mb-6">
    <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <span>Please correct the errors highlighted in the form.</span>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    <!-- Left Column: Core Details -->
    <div class="space-y-6">
      
      <!-- Basic Info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="sdiv mb-4" style="padding-top:0">Basic Information</div>

        <div class="grid grid-cols-1 gap-4">
          <!-- Ticket Searchable -->
          <div>
            <label class="flbl">Linked Ticket</label>
            <div class="relative">
              <input type="text" name="ticket_display" id="ticket-search" list="ticket-list" 
                     class="fin w-full <?= $e('ticket_id') ?>" 
                     placeholder="Type Ticket ID or Subject to search..."
                     value="<?= $v('ticket_display') ?: ($v('ticket_id') ? '#' . $v('ticket_number') : '') ?>">
              <input type="hidden" name="ticket_id" id="hidden-ticket-id" value="<?= $v('ticket_id') ?>">
              <datalist id="ticket-list">
                <?php foreach ($tickets as $tk): ?>
                  <option value="<?= htmlspecialchars($tk['ticket_number']) ?> — <?= htmlspecialchars($tk['title']) ?>" data-id="<?= $tk['ticket_id'] ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <p class="fhint">Enter Ticket # or keyword. Leave blank for Direct WO.</p>
            <?php if (isset($errors['ticket_id'])): ?><p class="ferr-msg"><?= $errors['ticket_id'] ?></p><?php endif; ?>
          </div>

          <!-- WO Type -->
          <div>
            <label class="flbl">Work Order Type <span class="text-red-400">*</span></label>
            <select name="wo_type" class="fsel <?= $e('wo_type') ?>" required>
              <?php foreach (['diagnosis'=>'Diagnosis','repair'=>'Repair','maintenance'=>'Maintenance','follow_up'=>'Follow-up'] as $k=>$label): ?>
                <option value="<?= $k ?>" <?= ($v('wo_type') ?: 'repair') === $k ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Assignment -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="sdiv mb-4" style="padding-top:0">Assignment & RMA</div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="flbl">Assign To</label>
            <div class="relative">
              <input type="text" id="assignee-search" list="tech-list" 
                     class="fin w-full <?= $e('assigned_to') ?>" 
                     placeholder="Search technician..."
                     value="<?= $wo['technician_name'] ?? '' ?>">
              <input type="hidden" name="assigned_to" id="hidden-assigned-to" value="<?= $v('assigned_to') ?>">
              <datalist id="tech-list">
                <?php foreach ($technicians as $t): ?>
                  <option value="<?= htmlspecialchars($t['full_name']) ?>" data-id="<?= $t['user_id'] ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
          </div>

          <div>
            <label class="flbl">Warranty RMA</label>
            <div class="flex items-center gap-2 mt-2">
              <input type="checkbox" name="is_rma" value="1" id="is-rma"
                     class="w-4 h-4 rounded border-gray-300 text-olfu-green focus:ring-green-500"
                     <?= ($old['is_rma'] ?? $wo['is_rma'] ?? 0) ? 'checked' : '' ?> />
              <label for="is-rma" class="text-sm text-gray-600 font-medium">Mark as RMA</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedule -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="sdiv mb-4" style="padding-top:0">Schedule</div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="flbl">Scheduled Start</label>
            <input type="datetime-local" id="f-start" name="scheduled_start" class="fin <?= $e('scheduled_start') ?>"
                   value="<?= $v('scheduled_start') ? (new DateTime($v('scheduled_start')))->format('Y-m-d\TH:i') : '' ?>" />
          </div>
          <div>
            <label class="flbl">Scheduled End</label>
            <input type="datetime-local" id="f-end" name="scheduled_end" class="fin <?= $e('scheduled_end') ?>"
                   value="<?= $v('scheduled_end') ? (new DateTime($v('scheduled_end')))->format('Y-m-d\TH:i') : '' ?>" />
          </div>
        </div>
        
        <!-- Error Msg for Dates -->
        <?php if (isset($errors['scheduled_end'])): ?><p class="ferr-msg mt-2"><?= $errors['scheduled_end'] ?></p><?php endif; ?>
        
        <!-- Conflict Warning -->
        <div id="conflict-warning" class="wo-banner banner-warn mt-4 hidden">
          <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
          <span id="conflict-msg" class="text-sm font-medium ml-2 text-red-800"></span>
        </div>
      </div>
    </div>

    <!-- Right Column: Status & Notes -->
    <div class="space-y-6">
      
      <!-- Status (edit only) -->
      <?php if ($is_edit): ?>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="sdiv mb-4" style="padding-top:0">Work Order Status</div>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="flbl">Current Status <span class="text-red-400">*</span></label>
            <select name="status" id="wo-status" class="fsel <?= $e('status') ?>" onchange="toggleHoldReason()">
              <?php foreach (['new'=>'New','assigned'=>'Assigned','scheduled'=>'Scheduled','in_progress'=>'In Progress','on_hold'=>'On Hold','resolved'=>'Resolved','closed'=>'Closed'] as $k=>$label): ?>
                <option value="<?= $k ?>" <?= ($v('status') ?: 'new') === $k ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['status'])): ?><p class="ferr-msg"><?= $errors['status'] ?></p><?php endif; ?>
          </div>
          <div id="hold-reason-wrap" class="<?= ($v('status') ?: '') !== 'on_hold' ? 'hidden' : '' ?>">
            <label class="flbl">On Hold Reason</label>
            <select name="on_hold_reason" class="fsel <?= $e('on_hold_reason') ?>">
              <option value="">— Select reason —</option>
              <?php foreach (['waiting_parts'=>'Waiting for parts','waiting_vendor'=>'Waiting for vendor','waiting_access'=>'Waiting for access','other'=>'Other'] as $k=>$label): ?>
                <option value="<?= $k ?>" <?= ($v('on_hold_reason') ?: '') === $k ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Notes -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="sdiv mb-4" style="padding-top:0">Technician Instructions & Notes</div>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="flbl">Work Notes</label>
            <textarea name="notes" rows="6" class="fin" placeholder="Initial instructions or observations for the technician…"><?= $v('notes') ?></textarea>
          </div>

          <?php if ($is_edit): ?>
          <div>
            <label class="flbl">Resolution Summary</label>
            <textarea name="resolution_notes" rows="4" class="fin" placeholder="What was the final outcome?"><?= $v('resolution_notes') ?></textarea>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-4">
        <a href="<?= $is_edit ? 'view.php?id=' . $wo['wo_id'] : 'index.php' ?>"
           class="text-sm font-semibold text-gray-500 hover:text-gray-800 px-6 py-2.5 rounded-xl hover:bg-gray-100 transition-all">
          Cancel
        </a>
        <button type="submit" id="submit-btn"
                class="inline-flex items-center gap-2 bg-olfu-green text-white text-sm font-bold px-8 py-3 rounded-xl hover:bg-olfu-green-md shadow-lg shadow-green-100 transition-all">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          <?= $is_edit ? 'Update Work Order' : 'Create Work Order' ?>
        </button>
      </div>
    </div>
  </div>
</form>

<script>
// --- Ticket Searchable Logic ---
const ticketSearch = document.getElementById('ticket-search');
const ticketList   = document.getElementById('ticket-list');
const hiddenTicketId = document.getElementById('hidden-ticket-id');

if (ticketSearch) {
    ticketSearch.addEventListener('input', function() {
        const val = this.value;
        const opts = ticketList.options;
        let foundId = '';
        for (let i = 0; i < opts.length; i++) {
            if (opts[i].value === val) {
                foundId = opts[i].dataset.id;
                break;
            }
        }
        hiddenTicketId.value = foundId;
    });
}

// --- Assignee Searchable Logic ---
const assigneeSearch = document.getElementById('assignee-search');
const techList       = document.getElementById('tech-list');
const hiddenAssigned = document.getElementById('hidden-assigned-to');

if (assigneeSearch) {
    assigneeSearch.addEventListener('input', function() {
        const val = this.value;
        const opts = techList.options;
        let foundId = '';
        for (let i = 0; i < opts.length; i++) {
            if (opts[i].value === val) {
                foundId = opts[i].dataset.id;
                break;
            }
        }
        hiddenAssigned.value = foundId;
        checkConflict(); // Trigger conflict check on assignment change
    });
}

function toggleHoldReason() {
  const wrap = document.getElementById('hold-reason-wrap');
  const sel  = document.getElementById('wo-status');
  if (wrap && sel) {
    wrap.classList.toggle('hidden', sel.value !== 'on_hold');
  }
}

// Double booking conflict checker
const fStart    = document.getElementById('f-start');
const fEnd      = document.getElementById('f-end');
const warnBox   = document.getElementById('conflict-warning');
const warnMsg   = document.getElementById('conflict-msg');
const woId      = <?= $is_edit ? $wo['wo_id'] : '0' ?>;

function checkConflict() {
  const assigned = hiddenAssigned.value;
  const start    = fStart.value;
  const end      = fEnd.value;
  
  // Reset UI
  warnMsg.textContent = "";
  warnBox.classList.add('hidden');
  warnBox.classList.replace('banner-danger', 'banner-warn');

  // 1. Basic validation: End must be after Start
  if (start && end) {
      if (new Date(end) <= new Date(start)) {
          warnMsg.textContent = "⚠️ Scheduled End must be AFTER the Start time.";
          warnBox.classList.remove('hidden');
          warnBox.classList.replace('banner-warn', 'banner-danger'); 
          return;
      }
  }

  // 2. AJAX conflict check
  if (!assigned || !start || !end) {
    return; // Stay hidden
  }
  
  const params = new URLSearchParams({
    assigned_to: assigned,
    start: start,
    end: end,
    wo_id: woId
  });
  
  fetch('check_conflicts_ajax.php?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (data.conflict) {
        warnMsg.textContent = "⚠️ Conflict: " + data.message;
        warnBox.classList.remove('hidden');
      } else {
        warnBox.classList.add('hidden');
      }
    })
    .catch(err => console.error(err));
}

if (fStart && fEnd) {
  fStart.addEventListener('change', checkConflict);
  fEnd.addEventListener('change', checkConflict);
}

// Initial state
document.addEventListener('DOMContentLoaded', () => {
    warnBox.classList.add('hidden');
});
</script>
