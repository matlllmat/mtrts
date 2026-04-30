<?php require __DIR__ . '/_styles.php'; ?>

<?php
/* ── Badge helper ───────────────────────────────────────────── */
$status = strtolower(trim(str_replace(' ', '_', $wo['status'] ?? '')));
$badge_cls = match($status) {
  'new'         => 'badge-new',
  'assigned'    => 'badge-assigned',
  'scheduled'   => 'badge-scheduled',
  'in_progress' => 'badge-in_progress',
  'on_hold'     => 'badge-on_hold',
  'resolved'    => 'badge-resolved',
  'closed'      => 'badge-closed',
  default       => 'badge-new',
};
$badge_label = match($status) {
  'new'         => 'New',
  'assigned'    => 'Assigned',
  'scheduled'   => 'Scheduled',
  'in_progress' => 'In Progress',
  'on_hold'     => 'On Hold',
  'resolved'    => 'Resolved',
  'closed'      => 'Closed',
  default       => ucfirst(str_replace('_', ' ', $status ?: 'new')),
};
$badge_base = 'display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;line-height:1.15;text-transform:none;white-space:nowrap;font-family:system-ui,sans-serif;';
$dot_color = match($status) {
  'new'         => '#9a9a9a',
  'assigned'    => '#3B82F6',
  'scheduled'   => '#3B82F6',
  'in_progress' => '#D97706',
  'on_hold'     => '#EC4899',
  'resolved'    => '#3B6D11',
  'closed'      => '#9a9a9a',
  default       => '#9a9a9a',
};
$badge_style = match($status) {
  'new'         => $badge_base . 'background:#f0f0ee;color:#3f3f3f;border:1px solid rgba(0,0,0,.06);',
  'assigned'    => $badge_base . 'background:#E6F1FB;color:#185FA5;border:1px solid rgba(59,130,246,.16);',
  'scheduled'   => $badge_base . 'background:#EFF6FF;color:#1D4ED8;border:1px solid rgba(59,130,246,.16);',
  'in_progress' => $badge_base . 'background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;',
  'on_hold'     => $badge_base . 'background:#FDF2F8;color:#9D174D;border:1px solid #FBCFE8;',
  'resolved'    => $badge_base . 'background:#EAF3DE;color:#27500A;border:1px solid #C0DD97;',
  'closed'      => $badge_base . 'background:#f0f0ee;color:#3f3f3f;border:1px solid rgba(0,0,0,.06);',
  default       => $badge_base . 'background:#f0f0ee;color:#3f3f3f;border:1px solid rgba(0,0,0,.06);',
};

/* ── Checklist / safety counts ──────────────────────────────── */
$sf_done  = count(array_filter($safety_checks, fn($i) => $i['is_done']));
$sf_total = count($safety_checks);

// Exclude photo items from manual checklist (they appear as auto-verified rows)
$photo_texts_lc = ['capture before-repair photo', 'capture after-repair photo'];
$manual_checklist = array_filter($checklist, fn($i) =>
  !in_array($i['verification_type'] ?? '', ['photo_before', 'photo_after']) &&
  !in_array(strtolower(trim($i['item_text'] ?? '')), $photo_texts_lc)
);
$cl_done_manual = count(array_filter($manual_checklist, fn($i) => $i['is_done']));

// Auto-verified rows: Time Logged, Signatory, Before Photo, After Photo
$auto_done = ($has_before_photo ? 1 : 0) + ($has_after_photo ? 1 : 0) + (!empty($signoff) ? 1 : 0) + (!empty($time_logs) ? 1 : 0);
$cl_done  = $cl_done_manual + $auto_done;
$cl_total = count($manual_checklist) + 4; // +4 auto-verified rows
?>

<!-- ── Breadcrumb ─────────────────────────────────────────────── -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm font-medium transition-colors"
     style="color:var(--tech-gray-500);"
     onmouseover="this.style.color='var(--tech-gray-900)'"
     onmouseout="this.style.color='var(--tech-gray-500)'">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to My Jobs
  </a>
  <span style="color:var(--tech-gray-200);">/</span>
</div>

<!-- ── WO Header card ─────────────────────────────────────────── -->
<div class="wo-header-card mb-5">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <!-- Left: title, tags, description -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 flex-wrap mb-2">
        <span class="vf-mono"><?php echo htmlspecialchars($wo['wo_number']); ?></span>

        <?php if (!empty($wo['assigned_to_name'])): ?>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                style="background:var(--tech-blue-lt);color:var(--tech-blue);">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <?php echo htmlspecialchars($wo['assigned_to_name']); ?>
          </span>
        <?php endif; ?>

        <?php if (!empty($status)): ?>
          <span id="woStatusBadge" class="wo-badge <?php echo $badge_cls; ?>" style="<?php echo $badge_style; ?>">
            <span class="bdot" style="width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0;background:<?php echo $dot_color; ?>;"></span><?php echo $badge_label; ?>
          </span>
        <?php endif; ?>

        <?php if (empty($can_edit)): ?>
          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold"
                style="background:var(--tech-gray-100);color:var(--tech-gray-500);">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            Read-only
          </span>
        <?php endif; ?>

        <?php if ($status === 'assigned' && !empty($can_edit)): ?>
          <button type="button"
                  id="startWorkBtn"
                  onclick="startWork(<?php echo (int)($wo['wo_id'] ?? 0); ?>, this)"
                  class="inline-flex items-center gap-1 text-white text-xs font-semibold px-2.5 py-1 rounded-md transition-colors"
                  style="background:var(--tech-green);"
                  onmouseover="this.style.background='var(--tech-green-dk)'"
                  onmouseout="this.style.background='var(--tech-green)'">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>
            </svg>
            Start Work
          </button>
        <?php endif; ?>
      </div>

      <h1 id="woTitle" class="text-xl font-bold mt-1"
          style="color:var(--tech-gray-900);font-family:var(--tech-sans);line-height:1.3;">
        <?php
          $title = $wo['ticket_title'] ?? '';
          if ($title === '') {
            $title = ucfirst(str_replace('_', ' ', trim((string)($wo['wo_type'] ?? ''))));
          }
          if ($title === '') {
            $title = 'Work Order';
          }
          echo htmlspecialchars($title);
        ?>
      </h1>
      <p id="woDesc" class="text-sm mt-0.5 line-clamp-2"
         style="color:var(--tech-gray-500);font-family:var(--tech-sans);">
        <?php
          $description = $wo['ticket_description'] ?? '';
          if ($description === '') {
            $description = $wo['notes'] ?? '';
          }
          echo htmlspecialchars($description);
        ?>
      </p>
    </div>

    <!-- Right: Sync button -->
    <div class="flex-shrink-0 flex items-start" style="margin-right:4px;">
      <button type="button" id="woSyncBtn"
              onclick="woHandleSync(this)"
              class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-md transition-colors"
              style="background:var(--olfu-green,#2d6a1f);color:#fff;border:none;cursor:pointer;white-space:nowrap;"
              onmouseover="this.style.opacity='.85'"
              onmouseout="this.style.opacity='1'">
        <svg class="w-3.5 h-3.5" id="woSyncIcon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span id="woSyncLabel">Sync</span>
      </button>
    </div>
  </div>

  <!-- Metadata strip -->
  <div class="grid grid-cols-2 gap-0 mt-5 pt-5"
       style="border-top:1px solid var(--tech-gray-100);">
    <div class="pr-4 md:pr-0 md:pl-0" style="border-right:1px solid var(--tech-gray-100);">
      <div class="vf-lbl">Priority</div>
      <?php
        $prio = strtolower($wo['priority'] ?? '');
        $prio_color = match($prio) {
          'high','urgent','critical' => 'var(--tech-red)',
          'medium','normal'          => 'var(--tech-amber)',
          default                    => 'var(--tech-gray-700)',
        };
      ?>
      <div id="woPriority" class="vf-val font-semibold"
           style="color:<?php echo $prio_color; ?>;">
        <?php echo ucfirst($wo['priority'] ?? '—'); ?>
      </div>
    </div>

    <div class="pl-0 md:pl-5 mt-3 md:mt-0">
      <div class="vf-lbl">Scheduled</div>
      <div class="vf-val text-sm">
        <?php if ($wo['scheduled_start'] ?? null): ?>
          <?php echo (new DateTime($wo['scheduled_start']))->format('M j, g:ia'); ?>
        <?php else: ?>
          <span class="vf-empty">Not set</span>
        <?php endif; ?>
      </div>
    </div>
  </div>





<!-- ══════════════════════════════════════════════════════════════
     Main tab body
     ══════════════════════════════════════════════════════════════ -->
<div class="rounded-xl overflow-hidden mb-4 mt-4"
     style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);">



  <!-- ── Secondary tabs (unified) ──────────────────────────── -->
  <div class="tab-nav secondary-tabs">

    <button class="tab-btn tab-on secondary-tab-btn"
            data-tab="safety" type="button"
            onclick="switchSecondaryTab('safety', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
      </svg>
      Safety
      <?php if ($sf_total > 0): ?>
        <span class="tab-count-badge <?php echo $sf_done === $sf_total ? 'done' : 'warn'; ?>" data-badge="safety">
          <?php echo $sf_done; ?>/<?php echo $sf_total; ?>
        </span>
      <?php endif; ?>
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="checklist" type="button"
            onclick="switchSecondaryTab('checklist', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Checklist
      <?php if ($cl_total > 0): ?>
        <span class="tab-count-badge <?php echo $cl_done === $cl_total ? 'done' : ''; ?>" data-badge="checklist">
          <?php echo $cl_done; ?>/<?php echo $cl_total; ?>
        </span>
      <?php endif; ?>
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="timetracking" type="button"
            onclick="switchSecondaryTab('timetracking', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Time Tracking
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="parts" type="button"
            onclick="switchSecondaryTab('parts', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
      </svg>
      Parts
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="communication" type="button"
            onclick="switchSecondaryTab('communication', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
      </svg>
      Notes
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="evidence" type="button"
            onclick="switchSecondaryTab('evidence', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
      </svg>
      Evidence
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="signoff" type="button"
            onclick="switchSecondaryTab('signoff', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
      </svg>
      Sign-off
    </button>
  </div>

  <!-- ════════════════════════════════════════════════════════════
       TAB PANES
       ════════════════════════════════════════════════════════════ -->

  <!-- ── Safety ─────────────────────────────────────────────── -->
  <div class="p-5" id="tab-safety">
    <div class="tech-card" style="margin-bottom:0;">
      <!-- Header -->
      <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--tech-gray-100);">
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:13px;font-weight:700;color:var(--tech-gray-900);">Safety Pre-Flight Checks</span>
          <span id="safetyProgress" style="font-size:12px;color:var(--tech-gray-400);font-weight:400;">
            <?php echo $sf_done . '/' . $sf_total . ' items'; ?>
          </span>
        </div>
      </div>

      <?php if ($sf_total > 0): ?>
        <div style="height:3px;background:var(--tech-gray-100);">
          <div class="safety-progress-fill" style="height:3px;background:var(--tech-green);width:<?php echo $sf_total ? round($sf_done/$sf_total*100) : 0; ?>%;transition:width .3s;"></div>
        </div>
      <?php endif; ?>

      <!-- Items -->
      <div id="safetyList">
        <?php if (!empty($safety_checks)): ?>
          <?php foreach ($safety_checks as $i => $check): ?>
            <?php $safetyText = trim($check['safety_text'] ?? $check['text'] ?? ''); ?>
            <?php if (($check['safety_id'] ?? $check['id'] ?? 0) === 0) continue; ?>
            <?php if ($safetyText === '') $safetyText = 'Safety glasses or face shield worn (when required)'; ?>
            <?php $safetyId = $check['safety_id'] ?? $check['id'] ?? 0; ?>
            <?php $isDone = (bool)($check['is_done'] ?? false); ?>
            <label class="checklist-row<?php echo $isDone ? ' checklist-row--done' : ''; ?>"
                   style="<?php echo $i < count($safety_checks)-1 ? 'border-bottom:1px solid var(--tech-gray-100);' : ''; ?>">
              <input type="checkbox"
                     class="checklist-cb"
                     data-safety="<?php echo $safetyId; ?>"
                     style="accent-color:#1a5c2a;width:14px;height:14px;"
                     <?php echo $isDone ? 'checked' : ''; ?>>
              <span class="checklist-text"><?php echo htmlspecialchars($safetyText); ?></span>
              <?php if (!empty($check['is_mandatory'])): ?>
                <span style="font-size:11px;color:var(--tech-red);flex-shrink:0;font-weight:600;">*</span>
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:40px 20px;text-align:center;">
            <p style="font-size:13px;color:var(--tech-gray-400);font-style:italic;">No safety checks configured for this work order.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Checklist ───────────────────────────────────────────── -->
  <div class="p-5 hidden" id="tab-checklist">
    <div class="tech-card" style="margin-bottom:0;">
      <!-- Header -->
      <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--tech-gray-100);">
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:13px;font-weight:700;color:var(--tech-gray-900);">
            <?php echo htmlspecialchars('General Repair Checklist'); ?>
          </span>
          <span id="checklistProgress" style="font-size:12px;color:var(--tech-gray-400);font-weight:400;">
            <?php
              echo $cl_done . '/' . $cl_total . ' items';
              if ($cl_total > 0) echo ' (' . round($cl_done / $cl_total * 100) . '%)';
            ?>
          </span>
        </div>
      </div>

      <?php if ($cl_total > 0): ?>
        <div style="height:3px;background:var(--tech-gray-100);">
          <div class="cl-progress-fill" style="height:3px;background:var(--tech-green);width:<?php echo $cl_total ? round($cl_done/$cl_total*100) : 0; ?>%;transition:width .3s;"></div>
        </div>
      <?php endif; ?>

      <!-- Items -->
      <div id="checklistList">
        <!-- Dynamic checklist items container (re-rendered by JS) -->
        <div id="checklistItems">
        <?php if (!empty($checklist)): ?>
          <?php
            // Filter out photo items (rendered as separate auto-verified rows below).
            // Match both by verification_type (DB rows) AND by item_text (fallback rows
            // that may not have verification_type set).
            $photo_texts = ['capture before-repair photo', 'capture after-repair photo'];
            $manual_items = array_filter($checklist, fn($item) =>
              !in_array($item['verification_type'] ?? '', ['photo_before', 'photo_after']) &&
              !in_array(strtolower(trim($item['item_text'] ?? '')), $photo_texts)
            );
          ?>
          <?php if (!empty($manual_items)): ?>
            <?php foreach ($manual_items as $i => $item): ?>
              <?php $isDone = (bool)$item['is_done']; ?>
              <label class="checklist-row<?php echo $isDone ? ' checklist-row--done' : ''; ?>"
                     style="<?php echo $i < count($manual_items)-1 ? 'border-bottom:1px solid var(--tech-gray-100);' : ''; ?>">
                <input type="checkbox"
                       class="checklist-cb"
                       data-check="<?php echo $item['item_id']; ?>"
                       style="accent-color:#1a5c2a;width:14px;height:14px;"
                       <?php echo $isDone ? 'checked' : ''; ?>>
                <span class="checklist-text"><?php echo htmlspecialchars($item['item_text']); ?></span>
                <?php if (!empty($item['is_mandatory'])): ?>
                  <span style="font-size:11px;color:var(--tech-red);flex-shrink:0;font-weight:600;">*</span>
                <?php endif; ?>
              </label>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="padding:40px 20px;text-align:center;">
              <p style="font-size:13px;color:var(--tech-gray-400);font-style:italic;">No manual checklist items. Photo captures are auto-verified below.</p>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div style="padding:40px 20px;text-align:center;">
            <p style="font-size:13px;color:var(--tech-gray-400);font-style:italic;">No checklist items assigned for this work order.</p>
          </div>
        <?php endif; ?>
        </div><!-- /checklistItems -->

        <!-- ══ AUTO-VERIFIED STATUS ROWS (not overwritten by JS) ════════ -->

        <!-- ── Work Time Logged row (auto-verified, non-clickable) ─────────────────────── -->
        <div class="checklist-row checklist-row--auto" id="clRowTimeTracking"
             style="border-top:1px solid var(--tech-gray-100);cursor:default;"
             title="Auto-verified: Log time in the Time Tracking tab">
          <!-- Visual checkbox: filled green when at least one entry logged -->
          <span id="clTimeCheckbox"
                style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;">
          </span>
          <span class="checklist-text" id="clTimeLabel">Work Time Logged</span>
          <span style="font-size:11px;color:var(--tech-red);flex-shrink:0;font-weight:600;">*</span>
          <svg style="width:14px;height:14px;flex-shrink:0;color:var(--tech-gray-400);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span id="clTimeStatus" 
                style="font-size:10px;color:#f59e0b;flex-shrink:0;white-space:nowrap;cursor:pointer;"
                onclick="switchSecondaryTab('timetracking', document.querySelector('[data-tab=timetracking]'))">Go to Time Tracking ↗</span>
        </div>

        <!-- ── Authorized Signatory Captured row (auto-verified, non-clickable) ──────────────────────────── -->
        <div class="checklist-row checklist-row--auto" id="clRowSignature"
             style="cursor:default;"
             title="Auto-verified: Capture signature in the Sign-off tab">
          <span id="clSigCheckbox"
                style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;">
          </span>
          <span class="checklist-text" id="clSigLabel">Authorized Signatory Captured</span>
          <span style="font-size:11px;color:var(--tech-red);flex-shrink:0;font-weight:600;">*</span>
          <svg style="width:14px;height:14px;flex-shrink:0;color:var(--tech-gray-400);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
          </svg>
          <span id="clSigStatus" 
                style="font-size:10px;color:#f59e0b;flex-shrink:0;white-space:nowrap;cursor:pointer;"
                onclick="switchSecondaryTab('signoff', document.querySelector('[data-tab=signoff]'))">Go to Sign-off ↗</span>
        </div>

        <!-- ── Capture Before-Repair Photo row (auto-verified, non-clickable) ──────────────────────────── -->
        <div class="checklist-row checklist-row--auto" id="clRowPhotoBeforeRow"
             style="cursor:default;"
             title="Auto-verified: Upload a before-repair photo in the Evidence tab">
          <span id="clPhotoBeforeCheckbox"
                style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;">
          </span>
          <span class="checklist-text" id="clPhotoBeforeLabel">Capture before-repair photo</span>
          <span style="font-size:11px;color:var(--tech-red);flex-shrink:0;font-weight:600;">*</span>
          <svg style="width:14px;height:14px;flex-shrink:0;color:var(--tech-gray-400);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
          </svg>
          <span id="clPhotoBeforeStatus" 
                style="font-size:10px;color:#f59e0b;flex-shrink:0;white-space:nowrap;cursor:pointer;"
                onclick="switchSecondaryTab('evidence', document.querySelector('[data-tab=evidence]'))">Go to Evidence ↗</span>
        </div>

        <!-- ── Capture After-Repair Photo row (auto-verified, non-clickable) ──────────────────────────── -->
        <div class="checklist-row checklist-row--auto" id="clRowPhotoAfterRow"
             style="cursor:default;"
             title="Auto-verified: Upload an after-repair photo in the Evidence tab">
          <span id="clPhotoAfterCheckbox"
                style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;">
          </span>
          <span class="checklist-text" id="clPhotoAfterLabel">Capture after-repair photo</span>
          <span style="font-size:11px;color:var(--tech-red);flex-shrink:0;font-weight:600;">*</span>
          <svg style="width:14px;height:14px;flex-shrink:0;color:var(--tech-gray-400);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
          </svg>
          <span id="clPhotoAfterStatus" 
                style="font-size:10px;color:#f59e0b;flex-shrink:0;white-space:nowrap;cursor:pointer;"
                onclick="switchSecondaryTab('evidence', document.querySelector('[data-tab=evidence]'))">Go to Evidence ↗</span>
        </div>
      </div>
    </div>

  </div><!-- /tab-checklist -->

  <!-- ── Time Tracking ───────────────────────────────────────── -->
  <div class="p-5 hidden" id="tab-timetracking">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">

      <!-- Timer controls -->
      <div style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);border-radius:var(--tech-radius-lg);overflow:hidden;">
        <!-- Header -->
        <div style="padding:14px 18px;border-bottom:1px solid var(--tech-gray-100);display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--olfu-green-50);">
              <svg style="width:15px;height:15px;color:var(--olfu-green);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--tech-gray-900);">Time Tracker</span>
          </div>
          <div id="timerState" style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--tech-gray-100);color:var(--tech-gray-500);">Not started</div>
        </div>

        <!-- Clock display -->
        <div style="padding:30px 20px 24px;text-align:center;border-bottom:1px solid var(--tech-gray-100);background:var(--tech-gray-50);">
          <span id="timerValue" style="font-family:var(--tech-mono);font-size:44px;font-weight:700;color:var(--tech-gray-900);letter-spacing:3px;line-height:1;display:block;">00:00:00</span>
          <p style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--tech-gray-400);margin-top:8px;">Elapsed time</p>
        </div>

        <!-- Controls -->
        <div style="padding:16px 18px;">
          <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--tech-gray-400);display:block;margin-bottom:6px;" for="laborType">Labor Type</label>
          <select id="laborType" class="fin text-sm w-full" style="margin-bottom:14px;">
            <option value="">Select labor type&hellip;</option>
            <option value="diagnosis">Diagnosis</option>
            <option value="repair">Repair</option>
            <option value="maintenance">Maintenance</option>
            <option value="follow_up">Follow-up</option>
          </select>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <button id="btnStart"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:7px;background:var(--olfu-green);color:#fff;font-size:13px;font-weight:600;padding:10px 0;border-radius:8px;border:none;cursor:pointer;transition:background .15s;font-family:inherit;"
                    onmouseover="this.style.background='var(--tech-green-dk)'"
                    onmouseout="this.style.background='var(--olfu-green)'">
              <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>
              </svg>
              Start
            </button>
            <button id="btnStop"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#fff;color:var(--tech-red);font-size:13px;font-weight:600;padding:10px 0;border-radius:8px;border:1.5px solid var(--tech-red);cursor:pointer;transition:all .15s;font-family:inherit;"
                    onmouseover="this.style.background='#fef2f2'"
                    onmouseout="this.style.background='#fff'">
              <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z"/>
              </svg>
              Stop &amp; Save
            </button>
          </div>
        </div>
      </div>

      <!-- Labor breakdown -->
      <div style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);border-radius:var(--tech-radius-lg);overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--tech-gray-100);display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--olfu-green-50);">
              <svg style="width:15px;height:15px;color:var(--olfu-green);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
              </svg>
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--tech-gray-900);">Labor Breakdown</span>
          </div>
          <span id="laborTotalBadge" class="hidden"
                style="font-size:12px;font-weight:700;font-family:var(--tech-mono);color:var(--olfu-green);background:var(--olfu-green-50);border:1px solid var(--olfu-green-100);padding:3px 10px;border-radius:999px;">
            0:00:00
          </span>
        </div>

        <div style="padding:16px 18px;">
          <div id="timeLogsList" class="space-y-2"></div>

          <div id="laborEmptyState" style="padding:32px 16px;text-align:center;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--tech-gray-100);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
              <svg style="width:16px;height:16px;color:var(--tech-gray-300);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <p style="font-size:12px;color:var(--tech-gray-400);font-style:italic;">No entries yet. Start the timer to log time.</p>
          </div>

          <div id="timeTotalRow" class="hidden" style="display:none;align-items:center;justify-content:space-between;margin-top:10px;padding:10px 14px;background:var(--olfu-green-50);border:1px solid var(--olfu-green-100);border-radius:8px;">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--olfu-green);">Total Time</span>
            <span id="timeTotalValue" style="font-family:var(--tech-mono);font-size:14px;font-weight:700;color:var(--olfu-green);">0:00:00</span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Parts ───────────────────────────────────────────────── -->
  <div class="p-6 hidden" id="tab-parts">
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l5.653-4.655m5.585-.359c.55-.157 1.12-.393 1.676-.752a12.07 12.07 0 00-9.12-9.12 7.094 7.094 0 00-.752 1.676m.359 5.596l5.877-5.877a2.652 2.652 0 113.75 3.75l-5.877 5.877"/>
          </svg>
          Parts Used
        </div>
      </div>
      <div class="tech-card__body">

        <!-- Mode toggle -->
        <div style="display:flex;gap:0;border:1px solid var(--tech-gray-200);border-radius:8px;overflow:hidden;width:fit-content;margin-bottom:1.25rem;">
          <button type="button" id="partsModeBrowse"
                  onclick="setPartsMode('browse')"
                  style="padding:6px 16px;font-size:12px;font-weight:500;background:#1a5c2a;color:#fff;border:none;cursor:pointer;font-family:inherit;">
            Browse by category
          </button>
          <button type="button" id="partsModeManual"
                  onclick="setPartsMode('manual')"
                  style="padding:6px 16px;font-size:12px;font-weight:500;background:none;border:none;cursor:pointer;color:var(--tech-gray-500);font-family:inherit;">
            Manual entry
          </button>
        </div>

        <!-- Browse panel -->
        <div id="partsPanelBrowse">
          <div style="font-size:11px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--tech-gray-400);margin-bottom:10px;">Category</div>

          <!-- Category tabs -->
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;" id="partsCatTabs">
            <?php
            $part_cats = [
              'all'        => 'All',
              'cables'     => 'Cables',
              'projector'  => 'Projector',
              'audio'      => 'Audio',
              'electrical' => 'Electrical',
              'electronic' => 'Electronic',
              'cooling'    => 'Cooling',
              'mounting'   => 'Mounting',
            ];
            foreach ($part_cats as $val => $lbl):
            ?>
            <button type="button"
                    class="parts-cat-tab <?= $val === 'all' ? 'parts-cat-tab--on' : '' ?>"
                    data-cat="<?= $val ?>"
                    onclick="setPartsCat('<?= $val ?>', this)">
              <?= $lbl ?>
            </button>
            <?php endforeach; ?>
          </div>

          <!-- Part chips grid -->
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-bottom:1rem;" id="partsChipGrid"></div>

          <!-- Selected preview -->
          <div id="partsSelPreview"
               style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;background:var(--tech-gray-50);border:1px solid var(--tech-gray-100);margin-bottom:12px;min-height:38px;">
            <span style="font-size:12px;font-style:italic;color:var(--tech-gray-400);">No part selected — tap a part above</span>
          </div>

          <!-- Qty + serial + add -->
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;flex-wrap:wrap;">
            <label style="font-size:12px;color:var(--tech-gray-500);white-space:nowrap;">Qty</label>
            <input class="fin text-sm" id="browsePartQty" type="number" min="1" value="1" style="width:70px;" />
            <label style="font-size:12px;color:var(--tech-gray-500);white-space:nowrap;">Serial (optional)</label>
            <input class="fin text-sm flex-1" id="browsePartSerial" placeholder="e.g. SN-00142" style="min-width:120px;" />
            <button type="button" id="btnAddBrowsePart"
                    class="inline-flex items-center justify-center gap-1.5 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap"
                    style="background:#9ca3af;cursor:not-allowed;"
                    disabled>
              + Add part
            </button>
          </div>
        </div>

        <!-- Manual entry panel -->
        <div id="partsPanelManual" style="display:none;">
          <div style="font-size:11px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--tech-gray-400);margin-bottom:10px;">Log manually</div>
          <div class="flex flex-wrap gap-2 mb-4">
            <input class="fin text-sm flex-1 min-w-36" id="partNumber" placeholder="Part number or name" />
            <input class="fin text-sm w-20" id="partQty" type="number" min="1" value="1" placeholder="Qty" />
            <input class="fin text-sm flex-1 min-w-36" id="partSerial" placeholder="Serial (optional)" />
            <button id="btnAddPart"
                    class="inline-flex items-center justify-center gap-1.5 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap"
                    style="background:var(--tech-green);"
                    onmouseover="this.style.background='var(--tech-green-dk)'"
                    onmouseout="this.style.background='var(--tech-green)'">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
              </svg>
              Add Part
            </button>
          </div>
        </div>

        <!-- Divider -->
        <div style="height:1px;background:var(--tech-gray-100);margin-bottom:1rem;"></div>

        <!-- Parts used list -->
        <div style="font-size:11px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--tech-gray-400);margin-bottom:10px;">
          Parts used <span id="partsCountLabel" style="font-size:11px;font-weight:400;letter-spacing:0;text-transform:none;color:var(--tech-gray-400);"></span>
        </div>
        <div id="partsList" class="space-y-2"><!-- Rendered by workorder.js --></div>

        <!-- Footer totals -->
        <div id="partsFooter" style="display:none;justify-content:space-between;align-items:center;margin-top:1rem;padding-top:12px;border-top:1px solid var(--tech-gray-100);">
          <span style="font-size:12px;color:var(--tech-gray-400);" id="partsFooterCount"></span>
          <span style="font-size:12px;color:var(--tech-gray-500);">Total qty: <strong id="partsFooterTotal" style="color:var(--tech-gray-700);"></strong></span>
        </div>

      </div>
    </div>
  </div>

  <style>
  .parts-cat-tab {
    padding: 5px 13px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid var(--tech-gray-200);
    background: none;
    cursor: pointer;
    color: var(--tech-gray-500);
    font-family: inherit;
    transition: all 0.12s;
  }
  .parts-cat-tab:hover { border-color: #86efac; background: #f0fdf4; color: #15803d; }
  .parts-cat-tab--on  { background: #1a5c2a !important; color: #fff !important; border-color: #1a5c2a !important; }

  .part-chip {
    display: flex;
    align-items: center;
    padding: 9px 12px;
    border: 1px solid var(--tech-gray-200);
    border-radius: 8px;
    background: var(--tech-surface);
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: all 0.12s;
  }
  .part-chip:hover   { border-color: #86efac; background: #f0fdf4; }
  .part-chip--on     { border-color: #15803d !important; background: #dcfce7 !important; }
  .part-chip__label  { font-size: 12px; font-weight: 500; color: var(--tech-gray-700); line-height: 1.3; }
  .part-chip__sub    { font-size: 10px; color: var(--tech-gray-400); margin-top: 1px; }

  .part-row-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 12px;
    padding: 9px 12px;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: all 0.12s;
  }
  .part-row-item:hover { background: #f0fdf4; border-color: #86efac; }
  .part-row-item:hover .part-row-item__remove { opacity: 1; }
  .part-row-item__name { font-size: 13px; font-weight: 500; color: var(--tech-gray-700); }
  .part-row-item__meta { font-size: 11px; color: var(--tech-gray-400); font-family: monospace; margin-top: 1px; }
  .part-row-item__cat  { font-size: 10px; font-weight: 500; padding: 1px 7px; border-radius: 999px; background: var(--tech-gray-100); color: var(--tech-gray-500); margin-left: 5px; vertical-align: middle; }
  .part-row-item__qty  { font-size: 11px; font-weight: 600; color: #166534; background: #dcfce7; border-radius: 999px; padding: 3px 10px; min-width: 34px; text-align: center; white-space: nowrap; }
  .part-row-item__remove { opacity: 0; width: 22px; height: 22px; border: none; background: none; cursor: pointer; border-radius: 4px; color: var(--tech-gray-400); font-size: 14px; display: flex; align-items: center; justify-content: center; transition: opacity 0.12s; }
  .part-row-item__remove:hover { color: #b91c1c; background: #fef2f2; }
  </style>

  <!-- ── Communication / Notes ───────────────────────────────── -->
  <div class="p-5 hidden" id="tab-communication">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">

      <!-- Left: Compose -->
      <div style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);border-radius:var(--tech-radius-lg);overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--tech-gray-100);display:flex;align-items:center;gap:8px;">
          <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--olfu-green-50);">
            <svg style="width:15px;height:15px;color:var(--olfu-green);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
            </svg>
          </span>
          <div>
            <p style="font-size:13px;font-weight:700;color:var(--tech-gray-900);line-height:1.2;">New Note</p>
            <p style="font-size:11px;color:var(--tech-gray-400);">Text or voice</p>
          </div>
        </div>
        <div style="padding:16px 18px;">
          <input id="noteTitle" type="text" class="fin text-sm w-full"
                 placeholder="Note title (optional)&hellip;"
                 style="margin-bottom:10px;" />
          <textarea id="noteText" rows="5" class="fin text-sm w-full resize-none"
                    placeholder="Add a progress note&hellip;"
                    style="margin-bottom:14px;"></textarea>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <button id="btnVoice"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;padding:8px 14px;border-radius:8px;border:1px solid var(--tech-gray-200);background:var(--tech-surface);color:var(--tech-gray-600);cursor:pointer;transition:background .15s;font-family:inherit;"
                    onmouseover="this.style.background='var(--tech-gray-50)'"
                    onmouseout="this.style.background='var(--tech-surface)'">
              <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/>
              </svg>
              Voice
            </button>
            <button id="btnAddNote"
                    style="display:inline-flex;align-items:center;gap:6px;background:var(--olfu-green);color:#fff;font-size:13px;font-weight:600;padding:8px 18px;border-radius:8px;border:none;cursor:pointer;transition:background .15s;font-family:inherit;"
                    onmouseover="this.style.background='var(--tech-green-dk)'"
                    onmouseout="this.style.background='var(--olfu-green)'">
              <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
              </svg>
              Add Note
            </button>
          </div>
        </div>
      </div>

      <!-- Right: Saved notes list -->
      <div style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);border-radius:var(--tech-radius-lg);overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--tech-gray-100);display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--olfu-green-50);">
              <svg style="width:15px;height:15px;color:var(--olfu-green);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--tech-gray-900);">Saved Notes</span>
          </div>
          <span id="notesCountBadge" style="font-size:11px;font-weight:600;color:var(--tech-gray-500);background:var(--tech-gray-100);padding:2px 8px;border-radius:999px;">0</span>
        </div>

        <div style="padding:16px 18px;">
          <div id="notesList" class="space-y-2"><!-- Rendered by workorder.js --></div>
          <!-- Empty state (hidden by JS when notes exist) -->
          <div id="notesEmptyState" style="padding:32px 16px;text-align:center;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--tech-gray-100);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
              <svg style="width:16px;height:16px;color:var(--tech-gray-300);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
              </svg>
            </div>
            <p style="font-size:12px;color:var(--tech-gray-400);font-style:italic;">No notes yet. Add one on the left.</p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Evidence (Documentation) ──────────────────────────────── -->
  <div class="p-6 hidden" id="tab-evidence">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

      <!-- Before -->
      <div class="tech-card" style="margin-bottom:0;">
        <div class="tech-card__head">
          <div class="tech-card__title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--tech-gray-400);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            Before
          </div>
          <span class="tech-card__meta">(<span id="beforeCount">0</span> files)</span>
        </div>
        <div class="tech-card__body">
          <label for="beforeFiles"
                 class="flex items-center gap-3 w-full rounded-lg px-4 py-2.5 cursor-pointer transition-colors group mb-3"
                 style="border:1px dashed var(--tech-gray-200);background:var(--tech-gray-50);"
                 onmouseover="this.style.borderColor='var(--tech-green-mid)';this.style.background='var(--tech-green-lt)'"
                 onmouseout="this.style.borderColor='var(--tech-gray-200)';this.style.background='var(--tech-gray-50)'">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                 style="color:var(--tech-gray-400);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            <span class="text-xs font-medium" style="color:var(--tech-gray-500);">Tap to capture / upload</span>
            <input id="beforeFiles" type="file" accept="image/*,video/*" capture="environment" multiple class="sr-only" />
          </label>
          <div class="grid grid-cols-2 gap-2" id="beforeMedia"></div>
        </div>
      </div>

      <!-- After -->
      <div class="tech-card" style="margin-bottom:0;">
        <div class="tech-card__head">
          <div class="tech-card__title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--tech-green);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            After
          </div>
          <span class="tech-card__meta">(<span id="afterCount">0</span> files)</span>
        </div>
        <div class="tech-card__body">
          <label for="afterFiles"
                 class="flex items-center gap-3 w-full rounded-lg px-4 py-2.5 cursor-pointer transition-colors group mb-3"
                 style="border:1px dashed var(--tech-gray-200);background:var(--tech-gray-50);"
                 onmouseover="this.style.borderColor='var(--tech-green-mid)';this.style.background='var(--tech-green-lt)'"
                 onmouseout="this.style.borderColor='var(--tech-gray-200)';this.style.background='var(--tech-gray-50)'">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                 style="color:var(--tech-gray-400);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            <span class="text-xs font-medium" style="color:var(--tech-gray-500);">Tap to capture / upload</span>
            <input id="afterFiles" type="file" accept="image/*,video/*" capture="environment" multiple class="sr-only" />
          </label>
          <div class="grid grid-cols-2 gap-2" id="afterMedia"></div>
        </div>
      </div>
    </div>

    <!-- Config backups -->
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Configuration Backups &amp; Logs
        </div>
        <span class="tech-card__meta">(<span id="configCount">0</span> files)</span>
      </div>
      <div class="tech-card__body">
        <label for="configFiles"
               class="flex items-center gap-3 w-full rounded-lg px-4 py-2.5 cursor-pointer transition-colors mb-3"
               style="border:1px dashed var(--tech-gray-200);background:var(--tech-gray-50);"
               onmouseover="this.style.borderColor='var(--tech-green-mid)';this.style.background='var(--tech-green-lt)'"
               onmouseout="this.style.borderColor='var(--tech-gray-200)';this.style.background='var(--tech-gray-50)'">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
               style="color:var(--tech-gray-400);">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <span class="text-xs font-medium" style="color:var(--tech-gray-500);">Upload config files, logs, backups</span>
          <span class="ml-auto text-xs hidden sm:inline" style="color:var(--tech-gray-400);">
            .json .xml .cfg .log .zip .tar&hellip; &middot; Max 50MB
          </span>
          <input id="configFiles"
                 type="file"
                 accept=".json,.xml,.cfg,.conf,.ini,.txt,.log,.csv,.zip,.tar,.gz,.bak,.img"
                 multiple class="sr-only" />
        </label>
        <div class="grid grid-cols-3 gap-2" id="configMedia"></div>
      </div>
    </div>
  </div>

  <!-- ── Sign-off (Documentation) ─────────────────────────────── -->
  <div class="p-6 hidden" id="tab-signoff">

    <!-- Validation blocker -->
    <div id="completeBlocker" class="mb-5 hidden p-4 rounded-lg" 
         style="background:var(--tech-amber-lt, #FEF3C7);border:1px solid var(--tech-amber, #D97706);color:var(--tech-amber-dk, #92400E);"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- Requester info fields -->
      <div class="tech-card" style="margin-bottom:0;background:var(--tech-gray-50);">
        <div class="tech-card__head" style="background:var(--tech-gray-50);">
          <div class="tech-card__title" style="color:var(--tech-green);">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
            Requester Sign-off
          </div>
        </div>
        <div class="tech-card__body space-y-4">
          <div>
            <label class="vf-lbl block mb-1.5" for="signerName">
              Full name <span style="color:var(--tech-red);">*</span>
            </label>
            <input class="fin text-sm w-full" id="signerName" placeholder="e.g., Juan Dela Cruz" />
          </div>
          <div>
            <label class="vf-lbl block mb-1.5" for="signerId">
              ID number <span style="color:var(--tech-red);">*</span>
            </label>
            <input class="fin text-sm w-full" id="signerId" placeholder="e.g., 2021-00123" />
          </div>
          <div>
            <label class="vf-lbl block mb-1.5" for="signerEmail">
              Email address <span style="color:var(--tech-red);">*</span>
            </label>
            <input class="fin text-sm w-full" id="signerEmail" type="email" placeholder="e.g., j.delacruz@olfu.edu.ph" />
          </div>
          <div>
            <label class="vf-lbl block mb-1.5" for="signerPosition">Position / Role</label>
            <select class="fin text-sm w-full" id="signerPosition">
              <option value="">Select position…</option>
              <option value="Faculty">Faculty</option>
              <option value="Staff">Staff</option>
              <option value="Department Staff">Department Staff</option>
              <option value="IT Staff">IT Staff</option>
              <option value="IT Manager">IT Manager</option>
              <option value="Admin">Admin</option>
              <option value="Student">Student</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Signature canvas -->
      <div class="tech-card" style="margin-bottom:0;background:var(--tech-gray-50);">
        <div class="tech-card__head" style="background:var(--tech-gray-50);">
          <div class="tech-card__title" style="color:var(--tech-green);">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
            </svg>
            Digital Signature
          </div>
        </div>
        <div class="tech-card__body flex flex-col">
          <p class="text-xs mb-3" style="color:var(--tech-gray-400);">
            Sign using your mouse or finger in the box below.
          </p>
          <div class="relative flex-1 min-h-0">
            <canvas id="sigCanvas"
                    class="w-full block rounded-xl"
                    style="height:200px;touch-action:none;cursor:crosshair;
                           background:#fff;border:2px dashed var(--tech-gray-200);">
            </canvas>
            <div id="sigPlaceholder"
                 class="absolute inset-0 flex items-center justify-center pointer-events-none"
                 style="color:var(--tech-gray-300);">
              <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
              </svg>
              <span class="ml-2 text-sm font-medium">Sign here</span>
            </div>
          </div>
          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center gap-1.5 text-sm font-medium" id="sigStatus">
              <span class="w-2 h-2 rounded-full inline-block" style="background:var(--tech-red);"></span>
              <span style="color:var(--tech-gray-500);">Not signed</span>
            </div>
            <div class="flex gap-2">
              <button id="btnClearSig"
                      class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                      style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);color:var(--tech-gray-600);"
                      onmouseover="this.style.background='var(--tech-gray-50)'"
                      onmouseout="this.style.background='var(--tech-surface)'">
                Clear
              </button>
              <button id="btnSaveSig"
                      class="text-xs font-semibold text-white px-3 py-1.5 rounded-lg transition-colors"
                      style="background:var(--tech-green);"
                      onmouseover="this.style.background='var(--tech-green-dk)'"
                      onmouseout="this.style.background='var(--tech-green)'">
                Save signature
              </button>
            </div>
          </div>

          <!-- ── Saved signature preview ─────────────────────── -->
          <div id="savedSigPreviewWrap" style="display:none;margin-top:14px;border-top:1px solid var(--tech-gray-100);padding-top:14px;">
            <div style="font-size:11px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--tech-gray-400);margin-bottom:8px;">Saved Signature</div>
            <div style="position:relative;border-radius:10px;border:1.5px solid var(--tech-green-bd,#86efac);background:#f0fdf4;padding:12px 16px;display:flex;align-items:center;gap:12px;">
              <img id="savedSigPreviewImg" src="" alt="Saved signature"
                   style="max-height:72px;max-width:260px;object-fit:contain;display:block;"/>
              <div style="display:flex;flex-direction:column;gap:4px;margin-left:auto;">
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:#dcfce7;color:#15803d;border:1px solid #86efac;">
                  <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                  Signed
                </span>
                <span id="savedSigSignerName" style="font-size:11px;color:var(--tech-gray-500);text-align:right;"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Satisfaction rating -->
    <div class="tech-card mt-6">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
          </svg>
          Service Satisfaction Rating
        </div>
      </div>
      <div class="tech-card__body">
        <p class="text-xs mb-4" style="color:var(--tech-gray-500);">
          How satisfied are you with the service provided? (1–5 stars)
        </p>
        <div class="flex items-center gap-3 mb-4">
          <div class="flex gap-1" id="satisfactionStars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" class="satisfaction-star" data-rating="<?php echo $s; ?>">
              <svg class="w-8 h-8 transition-colors" fill="currentColor" viewBox="0 0 24 24"
                   style="color:var(--tech-gray-200);">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
            </button>
            <?php endfor; ?>
          </div>
          <span class="text-sm font-medium" id="satisfactionText" style="color:var(--tech-gray-500);">Please rate</span>
          <input type="hidden" id="satisfactionRating" name="satisfaction" value="">
        </div>
        <div>
          <label class="vf-lbl block mb-1.5" for="satisfactionFeedback">
            Additional feedback (optional)
          </label>
          <textarea id="satisfactionFeedback" class="fin text-sm w-full resize-none" rows="3"
                    placeholder="Tell us about your experience with this service…"></textarea>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="mt-5 flex justify-end gap-3">
      <button id="btnSaveDraft"
              class="inline-flex items-center gap-1.5 text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
              style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);color:var(--tech-gray-700);"
              onmouseover="this.style.background='var(--tech-gray-50)'"
              onmouseout="this.style.background='var(--tech-surface)'">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Save Draft
      </button>
      <button id="btnComplete"
              class="inline-flex items-center gap-1.5 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
              style="background:var(--tech-green);"
              onmouseover="this.style.background='var(--tech-green-dk)'"
              onmouseout="this.style.background='var(--tech-green)'">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Complete Work Order
      </button>
    </div>
  </div>

</div><!-- /main tab body -->

<?php
/* ── JSON payload for workorder.js ────────────────────────── */
$__wo_payload = [
  'id'          => $wo['wo_id'],
  'wo_number'   => $wo['wo_number'],
  'title'       => $wo['ticket_title'] ?: ucfirst(str_replace('_', ' ', trim((string)($wo['wo_type'] ?? '')))),
  'description' => $wo['ticket_description'] ?: $wo['notes'],

  'status'      => $wo['status'],
  'priority'    => $wo['priority'],
  'location'    => ($wo['building'] ?? '') . ' • ' . ($wo['floor'] ?? '') . ' • ' . ($wo['room'] ?? ''),
  'requester'   => [
    'name'  => $wo['requester_name'],
    'phone' => $wo['contact_number'],
    'email' => $wo['email'],
  ],
  'assigned_to' => [
    'id'   => $wo['assigned_to'],
    'name' => $wo['assigned_to_name'] ?? null,
  ],
  // ─────────── send ALL checklist items (including photo items) ───────────
  'checklist' => array_map(fn($item) => [
    'id'                => $item['item_id'],
    'text'              => $item['item_text'],
    'required'          => $item['is_mandatory'],
    'requires_photo'    => (bool)($item['requires_photo'] ?? false),
    'is_verifiable'     => (bool)($item['is_verifiable'] ?? false),
    'verification_type' => $item['verification_type'] ?? null,
    'is_done'           => (bool)$item['is_done'],
  ], $checklist),
  'safety' => array_map(fn($s) => [
    'id'        => $s['safety_id'] ?? $s['id'] ?? 0,
    'text'      => $s['safety_text'] ?? $s['check_text'] ?? $s['text'] ?? '',
    'mandatory' => (bool)($s['is_mandatory'] ?? $s['mandatory'] ?? true),
    'is_done'   => (bool)($s['is_done'] ?? false),
  ], $safety_checks ?? []),
  'notes' => array_map(fn($n) => [
    'note_id'    => $n['note_id'] ?? null,
    'note_text'  => $n['note_text'] ?? null,
    'created_at' => $n['created_at'] ?? null,
    'created_by' => $n['created_by'] ?? null,
    'author'     => $n['author_name'] ?? $n['created_by'] ?? null,
  ], $notes ?? []),
  'media' => array_map(fn($m) => [
    'media_id'   => $m['media_id'],
    'media_type' => $m['media_type'],
    'file_path'  => $m['file_path'],
    'file_type'  => $m['file_type'],
    'caption'    => $m['caption'],
    'uploaded_at'=> $m['uploaded_at'],
  ], $media ?? []),
  'parts' => array_map(fn($p) => [
    'part_id'     => $p['part_id'],
    'part_number' => $p['part_number'],
    'quantity'    => $p['quantity'],
    'serial_no'   => $p['serial_no'],
    'added_at'    => $p['added_at'],
    'added_by'    => $p['added_by'],
  ], $parts ?? []),
  'time_logs'   => $time_logs ?? [],
  'total_time'  => $total_time ?? 0,
  'signoff' => $signoff ? [
    'signer_name'    => $signoff['signer_name'],
    'signature_path' => $signoff['signature_path'],
    'satisfaction'   => $signoff['satisfaction'],
    'feedback'       => $signoff['feedback'],
    'signed_at'      => $signoff['signed_at'],
  ] : null,
  'evidence_required'  => (bool)($evidence_required ?? false),
  'signature_required' => (bool)($signature_required ?? true),
  'can_edit'           => (bool)($can_edit ?? false),
  'can_execute_now'    => (bool)($can_execute_now ?? false),
  'can_claim'          => false,
];
$__wo_json = json_encode($__wo_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($__wo_json === false) {
  tech_dbg('H_WO_JSON', 'modules/technician/view.view.php:wo_json', 'json_encode failed for __WO_DATA__', [
    'wo_id'      => $wo_id,
    'json_error' => json_last_error_msg(),
  ]);
  $__wo_json = json_encode(['id' => $wo_id]);
}
?>

<script>
window.__WO_ID__   = <?php echo json_encode($wo_id); ?>;
window.__WO_DATA__ = <?php echo $__wo_json; ?>;

(function () {
  if (document.querySelector('link[rel="manifest"]')) return;
  const link = document.createElement('link');
  link.rel  = 'manifest';
  link.href = '<?php echo BASE_URL; ?>public/manifest.json';
  document.head.appendChild(link);
})();

window.MRTS = {
  APP_BASE: '<?php echo BASE_URL; ?>',
  USER_ID:  <?php echo json_encode($_SESSION['user_id'] ?? null); ?>
};

/* ── Primary tab switching ───────────────────────────────── */
function switchPrimaryTab(key, btn) {
  document.querySelectorAll('.primary-tab-btn').forEach(b => b.classList.remove('tab-on'));
  if (btn) btn.classList.add('tab-on');

  const isExec = (key === 'primary-execution');
  document.getElementById('execution-secondary-tabs').classList.toggle('hidden', !isExec);
  document.getElementById('documentation-secondary-tabs').classList.toggle('hidden', isExec);

  // Activate the first secondary tab for the chosen primary
  if (isExec) {
    const firstExecBtn = document.querySelector('#execution-secondary-tabs .secondary-tab-btn');
    if (firstExecBtn) switchSecondaryTab(firstExecBtn.dataset.tab, firstExecBtn, 'execution');
  } else {
    const firstDocBtn = document.querySelector('#documentation-secondary-tabs .secondary-tab-btn');
    if (firstDocBtn) switchSecondaryTab(firstDocBtn.dataset.tab, firstDocBtn, 'documentation');
  }
}

/* ── Secondary tab switching ───────────────────────────────── */
function switchSecondaryTab(key, btn, group) {
  // Deactivate all secondary tabs in this group
  const container = document.getElementById(group + '-secondary-tabs');
  if (container) {
    container.querySelectorAll('.secondary-tab-btn').forEach(b => b.classList.remove('tab-on'));
  }
  if (btn) btn.classList.add('tab-on');

  // Hide all tab panes
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));

  // Show target pane
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}

/* Legacy alias kept for workorder.js compatibility */
function switchTab(key, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-on'));
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));
  if (btn) btn.classList.add('tab-on');
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}

/* ── Update completion badges ────────────────────────────── */
function updateCompletionBadges() {
  if (!window.__WO_DATA__) return;

  // ── Safety ──────────────────────────────────────────────
  const safetyDone  = (window.__WO_DATA__.safety || []).filter(s => s.is_done).length;
  const safetyTotal = (window.__WO_DATA__.safety || []).length;

  const safetyBadge = document.querySelector('[data-badge="safety"]');
  if (safetyBadge) {
    safetyBadge.textContent = safetyDone + '/' + safetyTotal;
    safetyBadge.className = safetyDone === safetyTotal ? 'tab-count-badge done' : 'tab-count-badge warn';
  }
  const safetyProgress = document.getElementById('safetyProgress');
  if (safetyProgress) {
    safetyProgress.textContent = safetyDone + '/' + safetyTotal + ' complete';
    const safetyBar = document.querySelector('.safety-progress-fill');
    if (safetyBar) safetyBar.style.width = safetyTotal ? Math.round(safetyDone / safetyTotal * 100) + '%' : '0%';
  }

  // ── Checklist ────────────────────────────────────���───────
  // Manual items (exclude photo items — those are auto-verified rows)
  const photoTypes = ['photo_before', 'photo_after'];
  const photoTexts = ['capture before-repair photo', 'capture after-repair photo'];
  const manualItems = (window.__WO_DATA__.checklist || []).filter(c =>
    !photoTypes.includes(c.verification_type) &&
    !photoTexts.includes((c.text || '').toLowerCase().trim())
  );
  const manualDone = manualItems.filter(c => c.is_done).length;

  // Read auto-verified row state from DOM (set by workorder.js renderChecklistTimeLogs)
  // A row is "done" when its checkbox span contains an SVG checkmark (innerHTML !== '')
  function isAutoRowDone(checkboxId) {
    const el = document.getElementById(checkboxId);
    return el ? el.innerHTML.trim() !== '' : false;
  }
  const autoDone =
    (isAutoRowDone('clTimeCheckbox')        ? 1 : 0) +
    (isAutoRowDone('clSigCheckbox')          ? 1 : 0) +
    (isAutoRowDone('clPhotoBeforeCheckbox')  ? 1 : 0) +
    (isAutoRowDone('clPhotoAfterCheckbox')   ? 1 : 0);

  const checklistDone  = manualDone + autoDone;
  const checklistTotal = manualItems.length + 4; // +4 auto-verified rows

  const checklistBadge = document.querySelector('[data-badge="checklist"]');
  if (checklistBadge) {
    checklistBadge.textContent = checklistDone + '/' + checklistTotal;
    checklistBadge.className = checklistDone === checklistTotal ? 'tab-count-badge done' : 'tab-count-badge';
  }
  const checklistProgress = document.getElementById('checklistProgress');
  if (checklistProgress) {
    const pct = checklistTotal > 0 ? Math.round(checklistDone / checklistTotal * 100) : 0;
    checklistProgress.textContent = checklistDone + '/' + checklistTotal + ' items (' + pct + '%)';
    const checklistBar = document.querySelector('.cl-progress-fill');
    if (checklistBar) checklistBar.style.width = pct + '%';
  }
}

/* ── Start Work ──────────────────────────────────────────── */
function showConfirmModal(title, message, onConfirm) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.style.cssText = `
    position:fixed;top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.5);
    display:flex;align-items:center;justify-content:center;
    z-index:1000;
  `;

  const modal = document.createElement('div');
  modal.className = 'modal-dialog';
  modal.style.cssText = `
    background:white;border-radius:12px;padding:24px;
    max-width:400px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.2);
    animation:slideUp 0.3s ease-out;
  `;

  const titleEl = document.createElement('h3');
  titleEl.style.cssText = 'margin:0 0 12px 0;font-size:16px;font-weight:600;color:var(--tech-gray-900);';
  titleEl.textContent = title;

  const msgEl = document.createElement('p');
  msgEl.style.cssText = 'margin:0 0 24px 0;font-size:14px;color:var(--tech-gray-700);line-height:1.5;';
  msgEl.textContent = message;

  const btnGroup = document.createElement('div');
  btnGroup.style.cssText = 'display:flex;gap:12px;justify-content:flex-end;';

  const cancelBtn = document.createElement('button');
  cancelBtn.textContent = 'Cancel';
  cancelBtn.style.cssText = `
    padding:8px 16px;border:1px solid var(--tech-gray-200);
    border-radius:6px;background:white;color:var(--tech-gray-700);
    font-size:14px;font-weight:500;cursor:pointer;transition:all 0.2s;
  `;
  cancelBtn.onmouseover = () => cancelBtn.style.background = 'var(--tech-gray-50)';
  cancelBtn.onmouseout = () => cancelBtn.style.background = 'white';

  const confirmBtn = document.createElement('button');
  confirmBtn.textContent = 'Confirm';
  confirmBtn.style.cssText = `
    padding:8px 16px;border:none;border-radius:6px;
    background:var(--tech-green);color:white;
    font-size:14px;font-weight:500;cursor:pointer;transition:all 0.2s;
  `;
  confirmBtn.onmouseover = () => confirmBtn.style.background = 'var(--tech-green-dk)';
  confirmBtn.onmouseout = () => confirmBtn.style.background = 'var(--tech-green)';

  cancelBtn.onclick = () => overlay.remove();
  confirmBtn.onclick = () => {
    overlay.remove();
    onConfirm();
  };

  btnGroup.appendChild(cancelBtn);
  btnGroup.appendChild(confirmBtn);
  modal.appendChild(titleEl);
  modal.appendChild(msgEl);
  modal.appendChild(btnGroup);
  overlay.appendChild(modal);
  document.body.appendChild(overlay);
}

function showAlertModal(title, message) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.style.cssText = `
    position:fixed;top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.5);
    display:flex;align-items:center;justify-content:center;
    z-index:1000;
  `;

  const modal = document.createElement('div');
  modal.className = 'modal-dialog';
  modal.style.cssText = `
    background:white;border-radius:12px;padding:24px;
    max-width:400px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.2);
    animation:slideUp 0.3s ease-out;
  `;

  const titleEl = document.createElement('h3');
  titleEl.style.cssText = 'margin:0 0 12px 0;font-size:16px;font-weight:600;color:var(--tech-gray-900);';
  titleEl.textContent = title;

  const msgEl = document.createElement('p');
  msgEl.style.cssText = 'margin:0 0 20px 0;font-size:14px;color:var(--tech-gray-700);line-height:1.5;';
  msgEl.textContent = message;

  const okBtn = document.createElement('button');
  okBtn.textContent = 'OK';
  okBtn.style.cssText = `
    width:100%;padding:10px;border:none;border-radius:6px;
    background:var(--tech-green);color:white;
    font-size:14px;font-weight:500;cursor:pointer;transition:all 0.2s;
  `;
  okBtn.onmouseover = () => okBtn.style.background = 'var(--tech-green-dk)';
  okBtn.onmouseout = () => okBtn.style.background = 'var(--tech-green)';
  okBtn.onclick = () => overlay.remove();

  modal.appendChild(titleEl);
  modal.appendChild(msgEl);
  modal.appendChild(okBtn);
  overlay.appendChild(modal);
  document.body.appendChild(overlay);
}

function startWork(woId, button) {
  showConfirmModal(
    'Start Work',
    'Start work on this job? This will change the status to "In Progress".',
    () => {
      button.disabled = true;
      button.textContent = 'Starting…';

      fetch('<?php echo BASE_URL; ?>modules/technician/api/sync.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'start_work',
          wo_id: woId
        })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const badge = document.getElementById('woStatusBadge');
          if (badge) {
            badge.className = 'wo-badge badge-in_progress';
            badge.style.cssText = 'display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;line-height:1.15;text-transform:none;white-space:nowrap;font-family:system-ui,sans-serif;background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;';
            badge.innerHTML = '<span style="width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0;background:#D97706;"></span>In Progress';
          }
            if (window.__WO_DATA__) {
              window.__WO_DATA__.status = 'in_progress';
              window.__WO_DATA__.can_execute_now = true;
            }
            if (typeof window.setTechnicianEditable === 'function') {
              window.setTechnicianEditable(true);
            }
          button.remove();
          const msg = document.createElement('div');
          msg.className = 'text-sm font-medium px-3 py-2 rounded-lg mb-4';
          msg.style.cssText = 'background:var(--tech-green-lt);color:var(--tech-green-dk);border:1px solid var(--tech-green-bd);';
          msg.textContent = 'Work started successfully!';
          badge.parentElement.parentElement.prepend(msg);
          setTimeout(() => msg.remove(), 3000);
        } else {
          showAlertModal('Error', data.message || 'Failed to start work');
          button.disabled = false;
          button.textContent = 'Start Work';
        }
      })
      .catch(() => {
        showAlertModal('Error', 'Network error while starting work');
        button.disabled = false;
        button.textContent = 'Start Work';
      });
    }
  );
}

/* ── Satisfaction stars ──────────────────────────────────── */
(function () {
  const stars      = document.querySelectorAll('.satisfaction-star');
  const ratingIn   = document.getElementById('satisfactionRating');
  const ratingText = document.getElementById('satisfactionText');
  const labels     = ['', 'Very Dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very Satisfied'];

  function paintStars(n) {
    stars.forEach((s, i) => {
      s.querySelector('svg').style.color = (i < n) ? '#F59E0B' : 'var(--tech-gray-200)';
    });
  }

  stars.forEach(star => {
    star.addEventListener('click', function () {
      const r = parseInt(this.dataset.rating);
      ratingIn.value = r;
      ratingText.textContent = labels[r];
      paintStars(r);
    });
    star.addEventListener('mouseenter', function () { paintStars(parseInt(this.dataset.rating)); });
  });
  document.getElementById('satisfactionStars')?.addEventListener('mouseleave', () => {
    paintStars(parseInt(ratingIn.value) || 0);
  });
})();
</script>

<script src="<?php echo BASE_URL; ?>modules/technician/public/app.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/offline.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/idb-storage.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/signature.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/workorder.js"></script>

<script>
/* ── Parts list: handled by renderParts() in workorder.js ─── */

/* ── Show total time row when entries exist ────────────────── */
(function () {
  const totalRow = document.getElementById('timeTotalRow');
  const logsList = document.getElementById('timeLogsList');
  if (!totalRow || !logsList) return;
  const obs = new MutationObserver(() => {
    totalRow.classList.toggle('hidden', logsList.children.length === 0);
  });
  obs.observe(logsList, { childList: true });
  totalRow.classList.toggle('hidden', logsList.children.length === 0);
})();
</script>

<script>
/* ── WO-level Sync button ────────────────────────────────────
   Fixed behaviour:
   1. Push: flush offline action queue to server (syncNow).
   2. Pull: fetch current server state via get_state endpoint.
   3. Restore: merge server state + localStorage draft, re-render UI.
   Works both online (full sync) and offline (localStorage restore only).
──────────────────────────────────────────────────────────────── */
async function woHandleSync(btn) {
  const icon  = document.getElementById('woSyncIcon');
  const label = document.getElementById('woSyncLabel');
  btn.disabled = true;
  label.textContent = 'Syncing…';
  if (icon) icon.style.animation = 'spin 1s linear infinite';

  // Inject spin keyframe once
  if (!document.getElementById('_syncSpinStyle')) {
    const s = document.createElement('style');
    s.id = '_syncSpinStyle';
    s.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(s);
  }

  try {
    const online = window.MRTS && window.MRTS.offline
      ? await window.MRTS.offline.isReallyOnline()
      : navigator.onLine;

    let errorCount = 0;
    let conflictCount = 0;

    if (online && window.MRTS && window.MRTS.offline) {
      // Phase 1: Push queued offline actions to server
      label.textContent = 'Pushing changes…';
      const result = await window.MRTS.offline.syncNow();
      errorCount    = (result.errors    || []).length;
      conflictCount = (result.conflicts || []).length;
      // Phase 2: Pull + restore will happen inside restoreSyncedDraft
      label.textContent = 'Fetching latest…';
    } else {
      label.textContent = 'Offline – restoring…';
    }

    // Phase 3: Merge server state + localStorage draft and re-render UI
    if (typeof window.restoreSyncedDraft === 'function') {
      await window.restoreSyncedDraft();
    }

    // Set final label
    if (!online) {
      label.textContent = 'Offline – restored ✓';
    } else if (conflictCount) {
      label.textContent = `Synced (${conflictCount} conflict${conflictCount > 1 ? 's' : ''})`;
    } else if (errorCount) {
      label.textContent = `Synced (${errorCount} error${errorCount > 1 ? 's' : ''})`;
    } else {
      label.textContent = 'Synced ✓';
    }

    // Brief green flash
    btn.style.background = '#16a34a';
    setTimeout(() => {
      btn.style.background = '';
      label.textContent = 'Sync';
    }, 2500);

  } catch (e) {
    console.error('[v0] woHandleSync error:', e);
    label.textContent = 'Failed';
    btn.style.background = '#dc2626';
    // Even on error, try to restore from localStorage
    try {
      if (typeof window.restoreSyncedDraft === 'function') {
        await window.restoreSyncedDraft();
      }
    } catch (_) {}
    setTimeout(() => {
      btn.style.background = '';
      label.textContent = 'Sync';
    }, 2500);
  } finally {
    btn.disabled = false;
    if (icon) icon.style.animation = '';
  }
}
</script>