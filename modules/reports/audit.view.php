<!-- modules/reports/audit.view.php -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-gray-900">Audit & E-Discovery</h1>
        <p class="text-sm font-normal text-gray-500 mt-1">Searchable logs with automatic PII masking</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>modules/reports/index.php" class="bg-white text-gray-700 border border-gray-300 rounded-lg px-4 py-2 text-sm font-semibold hover:bg-gray-50 transition-colors shadow-sm">
            Back to Dashboard
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-12rem)]">
    <div class="p-4 border-b border-gray-100 bg-gray-50 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Date From</label>
            <input type="date" id="filter-date-from" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] focus:border-[#1a5c2a] outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Date To</label>
            <input type="date" id="filter-date-to" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] focus:border-[#1a5c2a] outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Object Type</label>
            <select id="filter-object" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#1a5c2a] focus:border-[#1a5c2a] outline-none">
                <option value="">All Types</option>
                <option value="ticket">Tickets</option>
                <option value="work_order">Work Orders</option>
                <option value="asset">Assets</option>
                <option value="user">Users</option>
            </select>
        </div>
        <div class="flex items-end">
            <button id="btn-search" class="w-full bg-[#1a5c2a] text-white rounded-lg px-4 py-2 text-sm font-semibold hover:bg-[#1f6e32] transition-colors shadow-sm">
                Search Logs
            </button>
        </div>
    </div>
    
    <div class="flex-1 overflow-auto">
        <table class="w-full text-left border-collapse min-w-[800px]">
            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                <tr>
                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Timestamp</th>
                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">User</th>
                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Action</th>
                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Object</th>
                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Changes</th>
                </tr>
            </thead>
            <tbody id="audit-tbody" class="divide-y divide-gray-100 text-sm">
                <tr><td colspan="5" class="py-8 text-center text-gray-400 italic">Enter search criteria and click Search</td></tr>
            </tbody>
        </table>
    </div>
    
    <div class="p-3 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
        <button id="btn-prev" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50">Previous</button>
        <span id="page-indicator" class="text-xs font-bold text-gray-500">Page 1</span>
        <button id="btn-next" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50">Next</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentPage = 1;
    
    const tbody = document.getElementById('audit-tbody');
    const btnSearch = document.getElementById('btn-search');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const pageIndicator = document.getElementById('page-indicator');
    
    const loadLogs = () => {
        const dateFrom = document.getElementById('filter-date-from').value;
        const dateTo = document.getElementById('filter-date-to').value;
        const objectType = document.getElementById('filter-object').value;
        
        tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400 italic">Loading...</td></tr>';
        btnPrev.disabled = true;
        btnNext.disabled = true;
        
        let url = `<?= BASE_URL ?>modules/reports/api_audit.php?page=${currentPage}&per=20`;
        if (dateFrom) url += `&date_from=${dateFrom}`;
        if (dateTo) url += `&date_to=${dateTo}`;
        if (objectType) url += `&object_type=${objectType}`;
        
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400 italic">No logs found.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.map(log => {
                    let changesHTML = '';
                    if (log.old_values || log.new_values) {
                        changesHTML = `<div class="max-w-xs xl:max-w-md overflow-hidden text-[11px] font-mono text-gray-500 whitespace-pre-wrap">${log.new_values || 'Deleted'}</div>`;
                    }
                    
                    return `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap text-gray-600">${log.created_at}</td>
                            <td class="py-3 px-4 font-medium text-gray-800">${log.user_name || 'System'}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider 
                                    ${log.action === 'CREATE' ? 'bg-green-100 text-green-800' : 
                                      log.action === 'UPDATE' ? 'bg-blue-100 text-blue-800' : 
                                      'bg-red-100 text-red-800'}">
                                    ${log.action}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                <span class="font-semibold">${log.object_type}</span> #${log.object_id}
                            </td>
                            <td class="py-3 px-4">${changesHTML}</td>
                        </tr>
                    `;
                }).join('');
                
                btnPrev.disabled = currentPage === 1;
                btnNext.disabled = data.length < 20;
                pageIndicator.textContent = `Page ${currentPage}`;
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-red-500">Error loading logs.</td></tr>';
            });
    };
    
    btnSearch.addEventListener('click', () => {
        currentPage = 1;
        loadLogs();
    });
    
    btnPrev.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadLogs();
        }
    });
    
    btnNext.addEventListener('click', () => {
        currentPage++;
        loadLogs();
    });
    
    // Initial load
    loadLogs();
});
</script>
