<!-- modules/reports/sla_policies.view.php — SLA Policy Editor -->
<div class="max-w-[1400px] mx-auto">

    <!-- Header -->
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-[#1a5c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                SLA Policy Editor
            </h1>
            <p class="text-sm text-gray-500 mt-1">Manage service level agreement policies. Changes to policies affecting active tickets require a justification for audit compliance.</p>
        </div>
        <div class="flex gap-3">
            <a href="<?= BASE_URL ?>modules/reports/index.php" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                Back to Reports
            </a>
            <button onclick="openPolicyModal(null)" class="bg-[#1a5c2a] text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#1f6e32] transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Policy
            </button>
        </div>
    </div>

    <!-- Alert container -->
    <div id="page-alert" class="hidden mb-4 px-4 py-3 rounded-lg text-sm font-semibold"></div>

    <!-- Policies Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider">Policy Name</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider">Priority</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider text-center">Response</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider text-center">Diagnosis</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider text-center">Resolution</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider text-center">Biz Hours</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($policies)): ?>
                        <tr><td colspan="10" class="px-6 py-12 text-center text-gray-400 italic">No SLA policies defined yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($policies as $p):
                        $pri_cls = match($p['priority'] ?? '') {
                            'critical' => 'bg-red-100 text-red-800 border-red-200',
                            'high'     => 'bg-orange-100 text-orange-800 border-orange-200',
                            'medium'   => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'low'      => 'bg-green-100 text-green-800 border-green-200',
                            default    => 'bg-gray-100 text-gray-700 border-gray-200',
                        };
                        $fmt = fn(int $m) => $m >= 60 ? floor($m/60).'h '.($m%60>0 ? ($m%60).'m' : '') : $m.'m';
                    ?>
                    <tr class="hover:bg-gray-50/60 transition-colors <?= $p['is_active'] ? '' : 'opacity-50' ?>">
                        <td class="px-4 py-3">
                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($p['policy_name']) ?></p>
                            <?php if ($p['is_event_support']): ?>
                                <span class="text-[9px] font-bold uppercase bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded border border-purple-200">Event Support</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($p['priority']): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?= $pri_cls ?>"><?= $p['priority'] ?></span>
                            <?php else: ?>
                                <span class="text-xs text-gray-400 italic">Any</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <?php if ($p['building']): ?>
                                <?= htmlspecialchars($p['building']) ?><?= $p['room'] ? ' / '.$p['room'] : '' ?>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-gray-700"><?= $fmt((int)$p['response_minutes']) ?></td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-gray-700"><?= $fmt((int)$p['diagnosis_minutes']) ?></td>
                        <td class="px-4 py-3 text-xs font-mono text-center font-bold text-[#1a5c2a]"><?= $fmt((int)$p['resolution_minutes']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($p['uses_business_hours']): ?>
                                <span class="text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded">Yes</span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-gray-500 bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded">24/7</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($p['is_active']): ?>
                                <span class="text-[10px] font-bold text-green-700 bg-green-50 border border-green-200 px-1.5 py-0.5 rounded">Active</span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-gray-500 bg-gray-100 border border-gray-200 px-1.5 py-0.5 rounded">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick='openPolicyModal(<?= json_encode($p) ?>)' class="text-[#1a5c2a] hover:text-black font-bold text-xs underline underline-offset-2 mr-3">Edit</button>
                            <button onclick='toggleActive(<?= $p['policy_id'] ?>, <?= $p['is_active'] ? 0 : 1 ?>)' class="text-gray-400 hover:text-red-600 font-bold text-xs underline underline-offset-2">
                                <?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-3 italic">Specificity scoring: Priority (10pts) &gt; Location (5pts) &gt; Category (3pts) &gt; Request Type (2pts). Higher score = policy applied first.</p>
</div>

<!-- Policy Modal -->
<div id="policy-modal" class="hidden fixed inset-0 z-50 bg-gray-900/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 id="modal-title" class="text-lg font-bold text-gray-900">New SLA Policy</h3>
            <button onclick="closePolicyModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="policy-form" class="p-6 space-y-4 overflow-y-auto max-h-[75vh]">
            <input type="hidden" id="f-policy-id" name="policy_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 mb-1">Policy Name *</label>
                    <input type="text" id="f-policy-name" name="policy_name" required maxlength="150"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Priority Match</label>
                    <select id="f-priority" name="priority" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                        <option value="">Any Priority</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Category Match</label>
                    <select id="f-category" name="category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                        <option value="">Any Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Location Match</label>
                    <select id="f-location" name="location_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                        <option value="">Any Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['building'] . ($loc['room'] ? ' / '.$loc['room'] : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Request Type Match</label>
                    <select id="f-request-type" name="request_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                        <option value="">Any Type</option>
                        <option value="repair">Repair</option>
                        <option value="replacement">Replacement</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="installation">Installation</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Response (minutes) *</label>
                    <input type="number" id="f-response" name="response_minutes" required min="1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Diagnosis (minutes) *</label>
                    <input type="number" id="f-diagnosis" name="diagnosis_minutes" required min="1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Resolution (minutes) *</label>
                    <input type="number" id="f-resolution" name="resolution_minutes" required min="1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                </div>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="f-biz-hours" name="uses_business_hours" value="1" checked class="rounded border-gray-300 text-[#1a5c2a] focus:ring-[#1a5c2a]">
                    <span class="text-sm font-medium text-gray-700">Respect Business Hours</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="f-event-support" name="is_event_support" value="1" class="rounded border-gray-300 text-[#1a5c2a] focus:ring-[#1a5c2a]">
                    <span class="text-sm font-medium text-gray-700">Event Support Override</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="f-active" name="is_active" value="1" checked class="rounded border-gray-300 text-[#1a5c2a] focus:ring-[#1a5c2a]">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>

            <!-- Justification (shown only on edit when policy has active tickets — enforced by backend, we always show on edit) -->
            <div id="justification-row" class="hidden">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-2">
                    <p class="text-xs font-bold text-amber-800">This policy may be applied to active tickets. A justification is required for compliance audit.</p>
                </div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Justification <span class="text-red-500">*</span></label>
                <textarea id="f-justification" name="justification" rows="2" maxlength="500"
                    placeholder="Reason for changing this policy..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] outline-none resize-none"></textarea>
            </div>

            <div id="modal-error" class="hidden text-sm text-red-600 font-semibold bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>

            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" onclick="closePolicyModal()" class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" id="modal-submit-btn" class="px-5 py-2 text-sm font-bold text-white bg-[#1a5c2a] rounded-lg hover:bg-[#1f6e32] transition shadow-sm">Save Policy</button>
            </div>
        </form>
    </div>
</div>

<script>
let editingPolicyId = null;

function openPolicyModal(policy) {
    editingPolicyId = policy ? policy.policy_id : null;
    document.getElementById('modal-title').textContent = policy ? 'Edit SLA Policy' : 'New SLA Policy';
    document.getElementById('modal-error').classList.add('hidden');

    // Reset form
    document.getElementById('policy-form').reset();
    document.getElementById('f-policy-id').value = editingPolicyId ?? '';

    if (policy) {
        document.getElementById('f-policy-name').value  = policy.policy_name || '';
        document.getElementById('f-priority').value     = policy.priority || '';
        document.getElementById('f-category').value     = policy.category_id || '';
        document.getElementById('f-location').value     = policy.location_id || '';
        document.getElementById('f-request-type').value = policy.request_type || '';
        document.getElementById('f-response').value     = policy.response_minutes || '';
        document.getElementById('f-diagnosis').value    = policy.diagnosis_minutes || '';
        document.getElementById('f-resolution').value   = policy.resolution_minutes || '';
        document.getElementById('f-biz-hours').checked     = !!parseInt(policy.uses_business_hours);
        document.getElementById('f-event-support').checked = !!parseInt(policy.is_event_support);
        document.getElementById('f-active').checked        = !!parseInt(policy.is_active);
        // Show justification row for edits
        document.getElementById('justification-row').classList.remove('hidden');
    } else {
        document.getElementById('f-biz-hours').checked = true;
        document.getElementById('f-active').checked    = true;
        document.getElementById('justification-row').classList.add('hidden');
    }

    document.getElementById('policy-modal').classList.remove('hidden');
}

function closePolicyModal() {
    document.getElementById('policy-modal').classList.add('hidden');
}

document.getElementById('policy-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('modal-submit-btn');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    const fd = new FormData(this);
    const body = new URLSearchParams();
    // Transfer FormData → URLSearchParams (handles checkboxes properly)
    for (const [k, v] of fd.entries()) body.append(k, v);
    // Explicitly send 0 for unchecked checkboxes
    ['uses_business_hours','is_event_support','is_active'].forEach(n => {
        if (!fd.has(n)) body.set(n, '0');
    });

    try {
        const res = await fetch('<?= BASE_URL ?>modules/reports/sla_policy_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        const json = await res.json();

        if (json.success) {
            closePolicyModal();
            showAlert(json.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            const err = document.getElementById('modal-error');
            err.textContent = json.message;
            err.classList.remove('hidden');
        }
    } catch (ex) {
        document.getElementById('modal-error').textContent = 'Network error. Please try again.';
        document.getElementById('modal-error').classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Policy';
    }
});

async function toggleActive(id, newVal) {
    const justification = newVal === 0
        ? prompt('Reason for deactivating this policy (required for audit log):')
        : null;
    if (newVal === 0 && !justification) return;

    const body = new URLSearchParams({ policy_id: id, is_active: newVal });
    if (justification) body.set('justification', justification);

    const res  = await fetch('<?= BASE_URL ?>modules/reports/sla_policy_save.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const json = await res.json();
    showAlert(json.message, json.success ? 'success' : 'error');
    if (json.success) setTimeout(() => location.reload(), 1000);
}

function showAlert(msg, type) {
    const el = document.getElementById('page-alert');
    el.textContent = msg;
    el.className = `mb-4 px-4 py-3 rounded-lg text-sm font-semibold ${type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'}`;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}
</script>
