<?php
// modules/workorders/_form.view.php — Shared form partial for add + edit.
// Variables expected: $is_edit, $wo, $errors, $old, $technicians, $tickets

$v = fn($k) => htmlspecialchars($old[$k] ?? $wo[$k] ?? '');
$e = fn($k) => isset($errors[$k]) ? 'fin-err' : '';

// Determine if RMA must be locked because the linked ticket's asset is under warranty
$_warranty_locked = false;
if ($is_edit && !empty($wo['wo_id'])) {
    if (!isset($_warranty_banner)) {
        $_warranty_banner = function_exists('get_wo_warranty_status')
            ? get_wo_warranty_status($pdo, (int)$wo['wo_id'])
            : ['parts_covered' => 0];
    }
    $_warranty_locked = !empty($_warranty_banner['parts_covered']);
} elseif (!empty($wo['ticket_id'])) {
    $_warranty_locked = ticket_has_active_parts_warranty($pdo, (int)$wo['ticket_id']);
}
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
            <label class="flbl">Linked Ticket <span class="text-red-400">*</span></label>
            <div class="relative">
              <input type="text" name="ticket_display" id="ticket-search" list="ticket-list" 
                     class="fin w-full <?= $e('ticket_id') ?>" 
                     placeholder="Search for a ticket (ID or Subject)..."
                     value="<?= $v('ticket_display') ?: ($v('ticket_id') ? '#' . $v('ticket_number') : '') ?>" required>
              <input type="hidden" name="ticket_id" id="hidden-ticket-id" value="<?= $v('ticket_id') ?>">
              <datalist id="ticket-list">
                <?php foreach ($tickets as $tk): ?>
                  <option value="<?= htmlspecialchars($tk['ticket_number']) ?> — <?= htmlspecialchars($tk['title']) ?>" data-id="<?= $tk['ticket_id'] ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <p class="fhint">Every work order must be linked to an active ticket.</p>
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
            <div class="flex items-center justify-between mb-1">
              <label class="flbl mb-0">Assign To</label>
              <button type="button" id="btn-suggest"
                      class="text-xs font-bold text-emerald-700 hover:underline disabled:text-gray-400 disabled:no-underline"
                      <?= empty($wo['ticket_id']) ? 'disabled' : '' ?>>
                ✨ Suggest technician
              </button>
            </div>
            <div class="relative">
              <input type="text" id="assignee-search" list="tech-list"
                     class="fin w-full <?= $e('assigned_to') ?>"
                     placeholder="Search technician..."
                     value="<?= $wo['technician_name'] ?? '' ?>"
                     data-user-touched="0">
              <input type="hidden" name="assigned_to" id="hidden-assigned-to" value="<?= $v('assigned_to') ?>">
              <datalist id="tech-list">
                <?php foreach ($technicians as $t): ?>
                  <option value="<?= htmlspecialchars($t['full_name']) ?>" data-id="<?= $t['user_id'] ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <p class="fhint">Leave blank to auto-assign on save based on skill + location.</p>
            <!-- Suggested candidates panel -->
            <div id="suggest-panel" class="mt-2 hidden">
              <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Top matches</div>
              <div id="suggest-list" class="space-y-1.5"></div>
            </div>
          </div>

          <div>
            <label class="flbl">Warranty RMA</label>
            <div class="flex items-center gap-2 mt-2">
              <input type="checkbox" name="is_rma" value="1" id="is-rma"
                     class="w-4 h-4 rounded border-gray-300 text-olfu-green focus:ring-green-500"
                     <?= ($_warranty_locked || ($old['is_rma'] ?? $wo['is_rma'] ?? 0)) ? 'checked' : '' ?>
                     <?= $_warranty_locked ? 'disabled' : '' ?> />
              <?php if ($_warranty_locked): ?>
                <input type="hidden" name="is_rma" value="1">
              <?php endif; ?>
              <label for="is-rma" class="text-sm <?= $_warranty_locked ? 'text-amber-700 font-semibold' : 'text-gray-600 font-medium' ?>">
                Mark as RMA<?= $_warranty_locked ? ' (locked)' : '' ?>
              </label>
            </div>
            <div id="rma-warranty-badge"
                 class="<?= $_warranty_locked ? 'flex' : 'hidden' ?> mt-2 items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
              </svg>
              Under warranty detected — RMA auto-enabled
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
        <div id="conflict-warning" class="wo-banner banner-warn mt-4" style="display: none;">
          <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
          <span id="conflict-msg" class="text-sm font-medium ml-2 text-red-800"></span>
        </div>
      </div>
      <!-- Parts Pre-allocation -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="sdiv mb-4 flex items-center justify-between" style="padding-top:0">
          <span>Parts Pre-allocation</span>
          <button type="button" onclick="addPartRow()" class="text-xs font-bold text-olfu-green hover:text-green-700 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Part
          </button>
        </div>

        <?php
          $_warranty_banner = null;
          if ($is_edit && !empty($wo['wo_id']) && function_exists('get_wo_warranty_status')) {
              $_warranty_banner = get_wo_warranty_status($pdo, (int)$wo['wo_id']);
          }
        ?>
        <?php if (!empty($_warranty_banner['parts_covered'])): ?>
          <div class="wo-banner banner-warn mb-3" style="margin-bottom:12px">
            <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>
              <strong>Asset under warranty</strong> (coverage: <?= htmlspecialchars($_warranty_banner['coverage_type']) ?>, expires <?= htmlspecialchars($_warranty_banner['warranty_end']) ?>) —
              parts used on this WO will display an <span class="rma-chip">RMA</span> flag; replacement should be claimed via vendor.
            </span>
          </div>
        <?php endif; ?>

        <div id="parts-container" class="space-y-3">
          <!-- Rows will be added here -->
        </div>

        <p class="text-xs text-gray-400 mt-4 italic">Note: Selected parts will be reserved for this work order.</p>
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
      <!-- Knowledge Aids -->
      <div id="kb-aids-section" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 <?= empty($kb_articles) ? 'hidden' : '' ?>">
        <div class="sdiv mb-4 flex items-center gap-2" style="padding-top:0">
          <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
          Knowledge Aids
        </div>
        <div id="kb-list" class="space-y-3">
          <?php if (!empty($kb_articles)): ?>
            <?php foreach ($kb_articles as $index => $kb): ?>
              <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-100">
                <h4 class="text-xs font-bold text-blue-800 mb-1"><?= htmlspecialchars($kb['title']) ?></h4>
                <p class="text-[11px] text-blue-600 line-clamp-2"><?= htmlspecialchars($kb['content']) ?></p>
                <button type="button" class="text-[10px] font-bold text-blue-700 mt-1 hover:underline" 
                        onclick="viewKbScript(<?= $index ?>)">
                  View full script
                </button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
// Key UI Elements
const ticketSearch = document.getElementById('ticket-search');
const ticketList   = document.getElementById('ticket-list');
const hiddenTicketId = document.getElementById('hidden-ticket-id');
const assigneeSearch = document.getElementById('assignee-search');
const techList       = document.getElementById('tech-list');
const hiddenAssigned = document.getElementById('hidden-assigned-to');
const fStart         = document.getElementById('f-start');
const fEnd           = document.getElementById('f-end');
const btnSuggest     = document.getElementById('btn-suggest');
const suggestPanel   = document.getElementById('suggest-panel');
const suggestList    = document.getElementById('suggest-list');
const warnBox        = document.getElementById('conflict-warning');
const warnMsg        = document.getElementById('conflict-msg');

// --- KB Script Viewer ---
let currentKbArticles = <?= json_encode($kb_articles) ?>;

function viewKbScript(index) {
    const kb = currentKbArticles[index];
    if (kb) {
        alert("KNOWLEDGE BASE: " + kb.title + "\n\n" + kb.content);
    }
}

// --- Ticket Searchable Logic ---

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
        
        // Refresh Knowledge Aids
        if (foundId) {
            checkWarranty(foundId);
            fetchKb(foundId);
        }
    });

    // Also check on blur to ensure we have a valid ID
    ticketSearch.addEventListener('blur', function() {
        if (!hiddenTicketId.value && this.value) {
            // Try to find it again if they pasted it or something
            const val = this.value;
            const opts = ticketList.options;
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value === val) {
                    hiddenTicketId.value = opts[i].dataset.id;
                    break;
                }
            }
        }
    });
}

const ticketsData = <?= json_encode($tickets) ?>;
function checkWarranty(ticketId) {
    const ticket      = ticketsData.find(t => t.ticket_id == ticketId);
    const rmaCheckbox = document.getElementById('is-rma');
    const rmaBadge    = document.getElementById('rma-warranty-badge');
    if (!rmaCheckbox) return;

    if (ticket && ticket.warranty_status === 'under_warranty') {
        rmaCheckbox.checked  = true;
        rmaCheckbox.disabled = true;
        // Ensure hidden fallback so disabled checkbox still submits
        if (!document.getElementById('is-rma-hidden')) {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'is_rma'; h.value = '1'; h.id = 'is-rma-hidden';
            rmaCheckbox.parentNode.appendChild(h);
        }
        const lbl = rmaCheckbox.nextElementSibling;
        if (lbl && lbl.tagName === 'LABEL') { lbl.textContent = 'Mark as RMA (locked)'; lbl.className = 'text-sm text-amber-700 font-semibold'; }
        if (rmaBadge) { rmaBadge.classList.remove('hidden'); rmaBadge.classList.add('flex'); }
    } else {
        rmaCheckbox.disabled = false;
        const h = document.getElementById('is-rma-hidden');
        if (h) h.remove();
        const lbl = rmaCheckbox.nextElementSibling;
        if (lbl && lbl.tagName === 'LABEL') { lbl.textContent = 'Mark as RMA'; lbl.className = 'text-sm text-gray-600 font-medium'; }
        if (rmaBadge) { rmaBadge.classList.add('hidden'); rmaBadge.classList.remove('flex'); }
    }
}

// Fire on page load for tickets pre-filled from the ticket detail "Create Work Order" button
if (hiddenTicketId && hiddenTicketId.value) {
    checkWarranty(hiddenTicketId.value);
}

function fetchKb(ticketId) {
    const section = document.getElementById('kb-aids-section');
    const list    = document.getElementById('kb-list');
    
    fetch('get_kb_ajax.php?ticket_id=' + ticketId)
        .then(r => r.json())
        .then(data => {
            if (data.kb && data.kb.length > 0) {
                currentKbArticles = data.kb; // Update global state
                section.classList.remove('hidden');
                list.innerHTML = data.kb.map((kb, idx) => `
                    <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-100">
                        <h4 class="text-xs font-bold text-blue-800 mb-1">${kb.title}</h4>
                        <p class="text-[11px] text-blue-600 line-clamp-2">${kb.content}</p>
                        <button type="button" class="text-[10px] font-bold text-blue-700 mt-1 hover:underline" 
                                onclick="viewKbScript(${idx})">
                            View full script
                        </button>
                    </div>
                `).join('');
            } else {
                section.classList.add('hidden');
                list.innerHTML = '';
            }
        })
        .catch(err => console.error(err));
}

// --- Assignee Searchable Logic ---

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
        // Once the user types anything in the assignee field, mark it as touched
        // so the suggest panel will not overwrite their choice.
        assigneeSearch.dataset.userTouched = '1';
        checkConflict(); // Trigger conflict check on assignment change
    });
}

// --- Suggest (Auto-assignment) Logic ---

function _autoAssignParams() {
    return new URLSearchParams({
        ticket_id: hiddenTicketId ? (hiddenTicketId.value || '') : '',
        start:     fStart ? (fStart.value || '') : '',
        end:       fEnd   ? (fEnd.value   || '') : '',
        wo_id:     <?= $is_edit ? (int)$wo['wo_id'] : 0 ?>
    });
}

function _renderCandidates(list, autoFill) {
    suggestList.innerHTML = '';
    if (!list || !list.length) {
        suggestPanel.classList.remove('hidden');
        suggestList.innerHTML = '<div class="text-[11px] text-gray-400 italic p-2 border border-dashed border-gray-200 rounded-lg text-center">No matching technicians found for this ticket.</div>';
        return;
    }
    suggestPanel.classList.remove('hidden');

    list.forEach((c, idx) => {
        const disqualified = (c.score < 0);
        const scoreColor = disqualified ? 'text-red-600'
                         : c.score >= 70 ? 'text-emerald-700'
                         : c.score >= 40 ? 'text-amber-700'
                         : 'text-gray-600';
        const matchBits = [];
        if (c.required_skills > 0) matchBits.push(`${c.matched_skills}/${c.required_skills} skills`);
        else                       matchBits.push('no skills required');
        if (c.loc_match === 'room')     matchBits.push('exact room');
        else if (c.loc_match === 'building') matchBits.push('same building');
        if (c.open_count !== undefined) matchBits.push(`${c.open_count} open WO${c.open_count === 1 ? '' : 's'}`);
        if (disqualified && c.conflict) matchBits.push('⚠ schedule conflict');

        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-2 p-2 rounded-lg border border-gray-100 hover:bg-emerald-50/40 cursor-pointer';
        row.innerHTML = `
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-gray-800 truncate">${c.full_name || ('User #' + c.user_id)}</div>
                <div class="text-[11px] text-gray-500">${matchBits.join(' • ')}</div>
            </div>
            <div class="text-right">
                <div class="text-sm font-bold ${scoreColor}">${disqualified ? '—' : c.score}</div>
                <div class="text-[10px] text-gray-400 uppercase tracking-wide">score</div>
            </div>
        `;
        row.addEventListener('click', () => {
            if (disqualified) return;
            hiddenAssigned.value = c.user_id;
            assigneeSearch.value = c.full_name || '';
            assigneeSearch.dataset.userTouched = '1';
            checkConflict();
        });
        suggestList.appendChild(row);

        // Auto-fill the top non-disqualified candidate when the user has not touched the field
        if (autoFill && idx === 0 && !disqualified && assigneeSearch.dataset.userTouched !== '1') {
            hiddenAssigned.value = c.user_id;
            assigneeSearch.value = c.full_name || '';
            checkConflict();
        }
    });
}

function fetchSuggestions(autoFill) {
    if (!hiddenTicketId || !hiddenTicketId.value) {
        suggestPanel.classList.add('hidden');
        if (btnSuggest) btnSuggest.disabled = true;
        return;
    }
    if (btnSuggest) {
        btnSuggest.disabled = true;
        const originalText = btnSuggest.innerHTML;
        btnSuggest.innerHTML = '<span class="flex items-center gap-1 animate-pulse">✨ Searching...</span>';
        
        fetch('suggest.php?' + _autoAssignParams().toString())
            .then(r => r.json())
            .then(data => {
                _renderCandidates(data.candidates || [], autoFill);
            })
            .catch(err => console.error('suggest:', err))
            .finally(() => {
                btnSuggest.disabled = false;
                btnSuggest.innerHTML = originalText;
            });
    }
}

if (btnSuggest) {
    btnSuggest.addEventListener('click', () => {
        // Explicit click = user wants a fresh suggestion; reset touched flag so the top candidate is auto-filled.
        if (assigneeSearch) assigneeSearch.dataset.userTouched = '0';
        fetchSuggestions(true);
    });
}

// Auto-trigger when the ticket changes
if (ticketSearch) {
    ticketSearch.addEventListener('change', () => fetchSuggestions(true));
}
// Re-fetch when schedule changes (so conflict disqualification refreshes)
if (fStart) fStart.addEventListener('change', () => fetchSuggestions(false));
if (fEnd)   fEnd.addEventListener('change',   () => fetchSuggestions(false));

// Initial load — only suggest+autofill when the form was opened with a pre-selected
// ticket and the assignee is empty (typical "Create WO from ticket" flow).
document.addEventListener('DOMContentLoaded', () => {
    if (hiddenTicketId && hiddenTicketId.value) {
        const autoFill = !hiddenAssigned.value;
        fetchSuggestions(autoFill);
    }
});

function toggleHoldReason() {
  const wrap = document.getElementById('hold-reason-wrap');
  const sel  = document.getElementById('wo-status');
  if (wrap && sel) {
    wrap.classList.toggle('hidden', sel.value !== 'on_hold');
  }
}

// Double booking conflict checker
const woId      = <?= $is_edit ? $wo['wo_id'] : '0' ?>;

function checkConflict() {
  const assigned = hiddenAssigned.value;
  const start    = fStart.value;
  const end      = fEnd.value;
  
  // Reset UI
  warnMsg.textContent = "";
  warnBox.style.display = 'none';
  warnBox.classList.replace('banner-danger', 'banner-warn');

  // 1. Basic validation: End must be after Start
  if (start && end) {
      if (new Date(end) <= new Date(start)) {
          warnMsg.textContent = "⚠️ Scheduled End must be AFTER the Start time.";
          warnBox.style.display = 'flex';
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
    wo_id: woId,
    ticket_id: hiddenTicketId.value
  });
  
  fetch('check_conflicts_ajax.php?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (data.conflict) {
        warnMsg.textContent = "⚠️ Conflict: " + data.message;
        warnBox.style.display = 'flex';
      } else {
        warnBox.style.display = 'none';
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
// --- Parts Logic ---
const allParts = <?= json_encode($all_parts) ?>;
const partsContainer = document.getElementById('parts-container');

function addPartRow(partId = '', qty = 1) {
    const rowId = 'part-row-' + Date.now();
    
    let optionsHtml = '<option value="">— Select Part —</option>';
    if (!allParts || allParts.length === 0) {
        optionsHtml = '<option value="">— No parts available —</option>';
    } else {
        optionsHtml += allParts.map(p => `
            <option value="${p.part_id}" ${p.part_id == partId ? 'selected' : ''}>
                ${p.part_name} (${p.part_number}) — Stock: ${p.quantity_on_hand}
            </option>
        `).join('');
    }

    const html = `
        <div id="${rowId}" class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-100">
            <div class="flex-1">
                <select name="parts[${rowId}][id]" class="fsel w-full text-xs" required ${!allParts || allParts.length === 0 ? 'disabled' : ''}>
                    ${optionsHtml}
                </select>
            </div>
            <div class="w-16">
                <input type="number" name="parts[${rowId}][qty]" value="${qty}" min="1" class="fin w-full text-xs" required ${!allParts || allParts.length === 0 ? 'disabled' : ''}>
            </div>
            <button type="button" onclick="document.getElementById('${rowId}').remove()" class="text-red-400 hover:text-red-600 p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    `;
    partsContainer.insertAdjacentHTML('beforeend', html);
}

// Pre-fill existing parts if any (on edit)
<?php 
if ($is_edit && !empty($parts)) {
    foreach ($parts as $p) {
        echo "addPartRow({$p['part_id']}, {$p['quantity_used']});\n";
    }
}
?>

if (!partsContainer.children.length && !<?= $is_edit ? 'true' : 'false' ?>) {
    // addPartRow(); // Optional: start with one empty row
}
</script>
