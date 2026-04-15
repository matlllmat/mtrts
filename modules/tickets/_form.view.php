<div class="mb-4 flex items-center justify-between">
  <div>
    <a href="<?= $is_edit ? "view.php?id={$t['ticket_id']}" : "index.php" ?>" class="text-sm text-olfu-green hover:underline flex items-center gap-1 mb-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Back
    </a>
    <h2 class="text-2xl font-bold text-gray-900 tracking-tight"><?= $is_edit ? 'Edit Ticket ' . htmlspecialchars($t['ticket_number']) : 'Submit New Ticket' ?></h2>
  </div>
</div>

<form action="save.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="ticket-form">
  <?php if ($is_edit): ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
  <?php else: ?>
    <input type="hidden" name="action" value="create">
  <?php endif; ?>
  
  <input type="hidden" name="requester_id" value="<?= htmlspecialchars($t['requester_id'] ?? $_SESSION['user_id']) ?>">

  <!-- Main Column -->
  <div class="lg:col-span-2 space-y-5">
    
    <!-- Issue Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 p-md-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Issue Details</h3>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Issue Title <span class="text-red-500">*</span></label>
          <input type="text" name="title" value="<?= htmlspecialchars($t['title']) ?>" required 
                 placeholder="Short, descriptive title" class="fin w-full" autofocus>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
          <textarea name="description" rows="5" required 
                    placeholder="Provide details about the issue..." class="fin w-full"><?= htmlspecialchars($t['description']) ?></textarea>
          <!-- Triage helper -->
          <div id="triage-helper" class="mt-2 hidden p-3 bg-amber-50 rounded border border-amber-100 text-sm text-amber-800">
            <div class="font-bold flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Suggested KB Article</div>
            <div id="triage-content" class="mt-1 flex gap-2 flex-wrap"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Location & Equipment -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 p-md-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Location & Equipment</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
          <select name="location_id" class="fsel w-full" required>
            <option value="">-- Select Location --</option>
            <?php foreach ($locations as $l): ?>
              <option value="<?= $l['location_id'] ?>" <?= $t['location_id'] == $l['location_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['building'] . ' - ' . $l['floor'] . ' - ' . $l['room']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag (Optional)</label>
           <select name="asset_id" id="asset-select" class="fsel w-full">
             <option value="">-- No specific asset / Unknown --</option>
             <?php foreach ($assets as $a): ?>
               <option value="<?= $a['asset_id'] ?>" <?= $t['asset_id'] == $a['asset_id'] ? 'selected' : '' ?>>
                 <?= htmlspecialchars($a['asset_tag'] . ' - ' . $a['manufacturer'] . ' ' . $a['model']) ?>
               </option>
             <?php endforeach; ?>
           </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
          <select name="category_id" id="category-select" class="fsel w-full" required>
            <option value="">-- Select Equipment Type --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['category_id'] ?>" data-bulb="<?= $c['has_bulb_hours'] ?>" <?= $t['category_id'] == $c['category_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      
      <!-- Dynamic fields (show via JS if projector selected) -->
      <div id="dynamic-fields-container" class="mt-4 p-4 bg-gray-50 rounded border border-gray-200 hidden">
        <h4 class="text-sm font-bold text-gray-700 mb-2">Category-Specific Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div id="df-bulb-hours" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">Projector Bulb Hours (if known)</label>
            <input type="number" name="dynamic_fields[bulb_hours]" value="<?= htmlspecialchars($dynamic_fields['bulb_hours'] ?? '') ?>" class="fin w-full" placeholder="e.g. 1500">
          </div>
          <div id="df-input-source" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">Failing Input Source</label>
            <input type="text" name="dynamic_fields[input_source]" value="<?= htmlspecialchars($dynamic_fields['input_source'] ?? '') ?>" class="fin w-full" placeholder="e.g. HDMI 1, VGA">
          </div>
        </div>
      </div>
    </div>
    
    <!-- Attachments (Create Only to simplify) -->
    <?php if (!$is_edit): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 p-md-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Attach Photos/Videos</h3>
      <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:bg-gray-50 transition-all cursor-pointer group">
        <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-olfu-green transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
        <div class="mt-4 flex text-sm text-gray-600 justify-center">
          <label class="relative cursor-pointer bg-white rounded-md font-medium text-olfu-green hover:text-olfu-green-md focus-within:outline-none">
            <span id="browse-label">Upload files</span>
            <input type="file" id="file-input" name="attachments[]" multiple class="sr-only" accept="image/*,video/*,.pdf">
          </label>
          <p class="pl-1">or drag and drop</p>
        </div>
        <p class="text-xs text-gray-500 mt-2" id="upload-preview">PNG, JPG, MP4, PDF up to 10MB</p>
      </div>
    </div>
    <?php endif; ?>
    
  </div>

  <!-- Sidebar Column -->
  <div class="space-y-5">
    
    <!-- Properties -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Triage</h3>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Impact Level <span class="text-red-500">*</span></label>
          <select name="impact" class="fsel w-full" required>
             <option value="low" <?= $t['impact'] == 'low' ? 'selected' : '' ?>>Low - Single user affected</option>
             <option value="medium" <?= $t['impact'] == 'medium' ? 'selected' : '' ?>>Medium - Small group</option>
             <option value="high" <?= $t['impact'] == 'high' ? 'selected' : '' ?>>High - Entire class/room</option>
             <option value="critical" <?= $t['impact'] == 'critical' ? 'selected' : '' ?>>Critical - Building/Campus</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Urgency <span class="text-red-500">*</span></label>
          <select name="urgency" class="fsel w-full" required>
             <option value="low" <?= $t['urgency'] == 'low' ? 'selected' : '' ?>>Low - When possible</option>
             <option value="medium" <?= $t['urgency'] == 'medium' ? 'selected' : '' ?>>Medium - Needs fix soon</option>
             <option value="high" <?= $t['urgency'] == 'high' ? 'selected' : '' ?>>High - Interrupting workflow</option>
             <option value="critical" <?= $t['urgency'] == 'critical' ? 'selected' : '' ?>>Critical - Immediate halt</option>
          </select>
        </div>
        
        <div class="flex items-center gap-2 mt-2 pt-4 border-t border-gray-100">
          <input type="checkbox" id="is_event_support" name="is_event_support" value="1" <?= $t['is_event_support'] ? 'checked' : '' ?> class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
          <label for="is_event_support" class="text-sm font-bold text-red-700">Urgent Event Support ⚡</label>
        </div>
        <p class="text-[11px] text-gray-500 leading-tight">Check this ONLY if this issue is blocking an event or class that is starting immediately.</p>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1 mt-2">Preferred Tech Window</label>
          <input type="datetime-local" name="preferred_window" value="<?= $t['preferred_window'] ? date('Y-m-d\TH:i', strtotime($t['preferred_window'])) : '' ?>" class="fin w-full">
        </div>
      </div>
    </div>
    
    <!-- Assigned state (Staff only during Edit) -->
    <?php if ($is_edit && $is_staff): ?>
    <div class="bg-blue-50 rounded-xl shadow-sm border border-blue-100 p-5">
      <h3 class="text-base font-bold text-blue-900 mb-4 pb-2 border-b border-blue-200">Staff Controls</h3>
      <div class="space-y-4 text-sm">
        <div>
          <label class="block font-medium text-blue-900 mb-1">Status</label>
          <select name="status" class="fsel w-full">
            <?php foreach(['new'=>'New','assigned'=>'Assigned','in_progress'=>'In Progress','on_hold'=>'On Hold','resolved'=>'Resolved','closed'=>'Closed','cancelled'=>'Cancelled'] as $k=>$v): ?>
               <option value="<?= $k ?>" <?= $t['status']==$k ? 'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block font-medium text-blue-900 mb-1">Assign To</label>
          <select name="assigned_to" class="fsel w-full">
            <option value="">-- Unassigned --</option>
            <?php foreach($assignables as $a): ?>
               <option value="<?= $a['user_id'] ?>" <?= $t['assigned_to']==$a['user_id'] ? 'selected':'' ?>><?= htmlspecialchars($a['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="pt-4">
      <button type="submit" class="w-full bg-olfu-green hover:bg-olfu-green-md text-white font-bold py-3 px-4 rounded-xl shadow-md transition-colors text-base">
        <?= $is_edit ? 'Save Changes' : 'Submit Ticket' ?>
      </button>
    </div>
  </div>
</form>

<script>
// Mock Triage Assistant for Description
const kbArticles = [
  { keywords: ['no signal', 'hdmi', 'vga', 'projector', 'black screen'], title: 'Projector: No Image / No Signal' },
  { keywords: ['bulb', 'lamp', 'projector', 'dark'], title: 'Projector: How to Check Bulb Hours' },
  { keywords: ['feedback', 'squeal', 'microphone', 'audio', 'sound'], title: 'Sound System: Feedback / High-Pitched Squeal' }
];

document.querySelector('textarea[name="description"]').addEventListener('input', function(e) {
  const text = e.target.value.toLowerCase();
  const triageContainer = document.getElementById('triage-helper');
  const triageContent = document.getElementById('triage-content');
  
  if (text.length < 10) {
    triageContainer.classList.add('hidden');
    return;
  }
  
  let suggestions = new Set();
  kbArticles.forEach(kb => {
    let matchCount = kb.keywords.filter(k => text.includes(k)).length;
    if (matchCount >= 2) {
      suggestions.add(kb.title);
    }
  });
  
  if (suggestions.size > 0) {
    triageContainer.classList.remove('hidden');
    triageContent.innerHTML = Array.from(suggestions).map(s => 
      `<span class="bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full text-xs font-semibold shadow-sm">${s}</span>`
    ).join('');
  } else {
    triageContainer.classList.add('hidden');
  }
});

// Dynamic Fields logic
const categorySelect = document.getElementById('category-select');
function updateDynamicFields() {
  const cSel = categorySelect;
  const selOpt = cSel.options[cSel.selectedIndex];
  if (!selOpt || !selOpt.value) {
    document.getElementById('dynamic-fields-container').classList.add('hidden');
    return;
  }
  
  const text = selOpt.text.toLowerCase();
  const hasBulb = selOpt.getAttribute('data-bulb') === '1';
  
  let showAny = false;
  
  const bDiv = document.getElementById('df-bulb-hours');
  if (hasBulb || text.includes('projector')) {
    bDiv.classList.remove('hidden');
    showAny = true;
  } else {
    bDiv.classList.add('hidden');
  }
  
  const iDiv = document.getElementById('df-input-source');
  if (text.includes('switcher') || text.includes('display') || text.includes('projector')) {
    iDiv.classList.remove('hidden');
    showAny = true;
  } else {
    iDiv.classList.add('hidden');
  }
  
  if (showAny) {
    document.getElementById('dynamic-fields-container').classList.remove('hidden');
  } else {
    document.getElementById('dynamic-fields-container').classList.add('hidden');
  }
}

if (categorySelect) categorySelect.addEventListener('change', updateDynamicFields);
updateDynamicFields();

// --- Attachment Handlers ---
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const previewText = document.getElementById('upload-preview');

if (dropZone && fileInput) {
  // Click to open file dialog
  dropZone.addEventListener('click', () => fileInput.click());

  // Prevent default behavior for drag events
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
    }, false);
  });

  // Visual cues for dragging
  ['dragenter', 'dragover'].forEach(evt => {
    dropZone.addEventListener(evt, () => dropZone.classList.add('bg-green-50', 'border-olfu-green'), false);
  });
  ['dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, () => dropZone.classList.remove('bg-green-50', 'border-olfu-green'), false);
  });

  // Handle dropped files
  dropZone.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    fileInput.files = dt.files;
    updateFilePreview();
  });

  // Handle selected files
  fileInput.addEventListener('change', updateFilePreview);

  function updateFilePreview() {
    const files = fileInput.files;
    if (files.length > 0) {
      previewText.innerHTML = `<span class="text-olfu-green font-bold">${files.length} file(s) selected</span><br>` + 
                             Array.from(files).map(f => f.name).join(', ');
      previewText.classList.remove('text-gray-500');
    } else {
      previewText.innerText = 'PNG, JPG, MP4, PDF up to 10MB';
      previewText.classList.add('text-gray-500');
    }
  }
}

// Auto-trigger descriptive triage logic if editing
setTimeout(() => {
  const descTxt = document.querySelector('textarea[name="description"]');
  if (descTxt) descTxt.dispatchEvent(new Event('input'));
}, 500);

</script>
