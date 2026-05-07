<!-- modules/reports/index.view.php -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-gray-900">SLA & Performance Analytics</h1>
        <p class="text-sm font-normal text-gray-500 mt-1">Module 5: System-wide analytics and audit reports</p>
    </div>
    <div class="flex gap-3">
        <select id="date-range" onchange="fetchStats()" class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 focus:ring-2 focus:ring-[#1a5c2a] focus:border-[#1a5c2a] outline-none shadow-sm">
            <option value="7">Last 7 Days</option>
            <option value="30" selected>Last 30 Days</option>
            <option value="90">Last 90 Days</option>
            <option value="365">This Year</option>
        </select>
        <button onclick="window.location.href='<?= BASE_URL ?>modules/reports/export.php?start=' + document.getElementById('date-range').value + '&end=' + new Date().toISOString().split('T')[0]" class="bg-[#1a5c2a] text-white rounded-lg px-4 py-2 text-sm font-semibold hover:bg-[#1f6e32] transition-colors shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Export
        </button>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
    <div onclick="openDrilldown('total')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <div class="flex items-center gap-1 mb-2 group/tooltip relative">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tickets</p>
            <svg onclick="showTerm('Total Tickets')" class="w-3.5 h-3.5 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-48 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Total number of tickets created within the selected date range.
            </div>
        </div>
        <div class="flex items-end justify-between">
            <span id="stat-total" class="text-3xl font-extrabold text-[#1a5c2a] leading-none">-</span>
        </div>
    </div>
    <div onclick="openDrilldown('breaches')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all group">
        <div class="flex items-center gap-1 mb-2 group/tooltip relative">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider group-hover:text-[#1a5c2a] transition-colors">SLA Compliance</p>
            <svg onclick="showTerm('SLA Compliance')" class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-48 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Percentage of tickets resolved without breaching their deadline. Click to view breaches.
            </div>
        </div>
        <div class="flex items-end justify-between">
            <span id="stat-compliance" class="text-3xl font-extrabold text-[#1a5c2a] leading-none">-</span>
            <span class="text-[10px] font-bold text-gray-400 uppercase group-hover:text-[#1a5c2a] transition-colors">View →</span>
        </div>
    </div>
    <div onclick="openDrilldown('mttr')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <div class="flex items-center gap-1 mb-2 group/tooltip relative">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Avg MTTR</p>
            <svg onclick="showTerm('Avg MTTR')" class="w-3.5 h-3.5 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-48 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Mean Time To Resolve: The average time taken to fix and close a ticket.
            </div>
        </div>
        <span id="stat-mttr" class="text-2xl font-extrabold text-gray-800 leading-none">-</span>
    </div>
    <div onclick="openDrilldown('ftfr')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <div class="flex items-center gap-1 mb-2 group/tooltip relative">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">FTFR</p>
            <svg onclick="showTerm('FTFR')" class="w-3.5 h-3.5 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-48 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                First-Time Fix Rate: Percentage of tickets resolved with only a single work order.
            </div>
        </div>
        <span id="stat-ftfr" class="text-2xl font-extrabold text-gray-800 leading-none">-</span>
    </div>
    <div onclick="openDrilldown('backlog')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-red-500 transition-all">
        <div class="flex items-center gap-1 mb-2 group/tooltip relative">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Backlog</p>
            <svg onclick="showTerm('Backlog')" class="w-3.5 h-3.5 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full right-0 mb-1 hidden group-hover/tooltip:block w-48 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Total number of unresolved tickets currently open in the system (Lifetime).
            </div>
        </div>
        <span id="stat-backlog" class="text-2xl font-extrabold text-red-600 leading-none">-</span>
    </div>

</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div onclick="openDrilldown('resolved')" class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 min-h-[320px] flex flex-col xl:col-span-2 cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all group">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-1 group/tooltip relative">
                <h3 class="font-bold text-gray-800 text-base group-hover:text-[#1a5c2a] transition-colors">Resolution Trends</h3>
                <svg onclick="showTerm('Resolution Trends')" class="w-4 h-4 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-64 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                    Tracks the total number of tickets successfully resolved per day within the selected timeframe.
                </div>
            </div>
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider bg-gray-50 px-2 py-1 rounded">Click for Details</span>
        </div>
        <div class="flex-1 relative w-full h-full min-h-[240px]">
            <canvas id="lineChart"></canvas>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 min-h-[320px] flex flex-col">
        <div class="flex items-center gap-1 mb-4 group/tooltip relative">
            <h3 class="font-bold text-gray-800 text-base">Frequent Failures</h3>
            <svg onclick="showTerm('Asset Hotspots')" class="w-4 h-4 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full right-0 mb-1 hidden group-hover/tooltip:block w-56 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Identifies equipment with the highest volume of reported issues.
            </div>
        </div>
        <div class="flex-1 overflow-y-auto pr-2" id="hotspots-container">
            <div class="flex justify-center items-center h-full text-sm text-gray-400 italic">Loading...</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 min-h-[320px] flex flex-col xl:col-span-2">
        <div class="flex items-center gap-1 mb-4 group/tooltip relative">
            <h3 class="font-bold text-gray-800 text-base">Escalations & Breaches</h3>
            <svg onclick="showTerm('SLA Compliance')" class="w-4 h-4 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-64 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Current tickets that have exceeded their SLA deadlines and require management intervention.
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Ticket</th>
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Assignee</th>
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Breach Type</th>
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Deadline</th>
                    </tr>
                </thead>
                <tbody id="escalations-tbody" class="divide-y divide-gray-50 text-[11px]">
                    <tr><td colspan="4" class="py-4 text-center text-gray-400 italic">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 min-h-[320px] flex flex-col">
        <div class="flex items-center gap-1 mb-4 group/tooltip relative">
            <h3 class="font-bold text-gray-800 text-base">Technician Scorecards</h3>
            <svg onclick="showTerm('Technician Scorecards')" class="w-4 h-4 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="overflow-y-auto pr-2">
            <table class="w-full text-left border-collapse">
                <tbody id="scorecards-tbody" class="divide-y divide-gray-50 text-[11px]">
                    <tr><td class="py-4 text-center text-gray-400 italic">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center gap-1 mb-4 group/tooltip relative">
            <h3 class="font-bold text-gray-800 text-base">Location Heatmap</h3>
            <svg onclick="showTerm('Asset Hotspots')" class="w-4 h-4 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-56 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Shows which rooms/buildings have the highest density of repair requests.
            </div>
        </div>
        <div class="space-y-2" id="heatmap-container">
            <div class="flex justify-center items-center h-20 text-sm text-gray-400 italic">Loading...</div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center gap-1 mb-4 group/tooltip relative">
            <h3 class="font-bold text-gray-800 text-base">Warranty Exposure</h3>
            <svg onclick="showTerm('Warranty Exposure')" class="w-4 h-4 text-gray-400 hover:text-[#1a5c2a] cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="absolute bottom-full left-0 mb-1 hidden group-hover/tooltip:block w-56 p-2 bg-gray-800 text-white text-[10px] normal-case tracking-normal font-normal rounded shadow-lg z-50 pointer-events-none">
                Assets with manufacturer warranty expiring in the next 90 days.
            </div>
        </div>
        <div class="space-y-2" id="warranty-container">
            <div class="flex justify-center items-center h-20 text-sm text-gray-400 italic">Loading...</div>
        </div>
    </div>

    <div class="bg-[#f0fdf4] rounded-xl p-5 shadow-sm border border-[#dcfce7]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-[#166534] text-base">Audit & Compliance</h3>
            <span class="bg-[#dcfce7] text-[#166534] text-[10px] font-bold px-2 py-1 rounded tracking-wider uppercase border border-green-200">Secure</span>
        </div>
        <p class="text-xs text-gray-600 mb-4 italic">Immutable audit log active. PII data is masked for non-admin users to ensure compliance with privacy standards.</p>
        
        <div class="grid grid-cols-1 gap-2 mt-auto">
            <a href="<?= BASE_URL ?>modules/reports/audit.php" class="flex items-center justify-center gap-2 text-sm font-semibold text-white bg-[#1a5c2a] px-4 py-2 rounded-lg shadow-sm hover:bg-[#1f6e32] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                View Searchable Logs (E-discovery)
            </a>
            <a href="<?= BASE_URL ?>api/analytics" class="flex items-center justify-center gap-2 text-sm font-semibold text-[#1a5c2a] bg-white px-4 py-2 rounded-lg shadow-sm border border-[#dcfce7] hover:bg-green-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                REST BI Connector
            </a>
        </div>
    </div>
</div>

<!-- Drill-down Modal -->
<div id="drilldown-modal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 id="drilldown-title" class="text-lg font-bold text-[#1a5c2a]">Ticket Details</h3>
                <p class="text-xs font-normal text-gray-500 mt-1">Showing records associated with the selected metric.</p>
            </div>
            <button onclick="document.getElementById('drilldown-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-0">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white sticky top-0 shadow-sm">
                    <tr>
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Ticket</th>
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Requester</th>
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Priority</th>
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Created At</th>
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 text-right">Deadline</th>
                    </tr>
                </thead>
                <tbody id="drilldown-tbody" class="divide-y divide-gray-50 text-sm">
                    <tr><td colspan="5" class="py-8 text-center text-gray-400 italic">Loading records...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Terminology/Tooltip Modal -->
<div id="term-modal" class="fixed inset-0 z-[60] hidden bg-gray-900 bg-opacity-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#1a5c2a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 id="term-title" class="text-xl font-bold text-gray-900">Term Name</h3>
            </div>
            <div id="term-content" class="text-sm text-gray-600 leading-relaxed space-y-3">
                <!-- Content here -->
            </div>
            <button onclick="document.getElementById('term-modal').classList.add('hidden')" class="mt-6 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-lg transition-colors">
                Got it
            </button>
        </div>
    </div>
</div>

<script>
const TERMS = {
    'SLA Compliance': {
        desc: 'SLA (Service Level Agreement) Compliance measures how many tickets were resolved within the promised timeframe.',
        impact: 'A high rate means we are meeting our promises to departments. Low rates indicate either a lack of resources or unrealistic targets.'
    },
    'Avg MTTR': {
        desc: 'MTTR (Mean Time To Resolve) is the average time it takes for a ticket to go from "New" to "Resolved".',
        impact: 'Shorter MTTR means faster service recovery for the user. It is a key indicator of technician efficiency.'
    },
    'FTFR': {
        desc: 'FTFR (First Time Fix Rate) measures the percentage of repairs completed successfully in a single work order without needing follow-up visits.',
        impact: 'High FTFR reduces costs and improves user satisfaction as it minimizes equipment downtime.'
    },
    'Backlog': {
        desc: 'Total number of active, unresolved tickets currently in the system.',
        impact: 'A growing backlog suggests that the rate of incoming requests is higher than the completion rate.'
    },
    'Asset Hotspots': {
        desc: 'Specific equipment models or locations that report failures significantly more often than others.',
        impact: 'Used to justify equipment replacements or perform preventive maintenance on specific brands.'
    }
};

function showTerm(termKey) {
    const term = TERMS[termKey];
    if (!term) return;
    document.getElementById('term-title').textContent = termKey;
    document.getElementById('term-content').innerHTML = `
        <p>${term.desc}</p>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded">
            <p class="text-xs font-bold text-blue-800 uppercase mb-1">Why it matters</p>
            <p class="text-xs text-blue-700">${term.impact}</p>
        </div>
    `;
    document.getElementById('term-modal').classList.remove('hidden');
    event.stopPropagation(); // Prevent drilldown from opening if clicking tooltip
}



const formatMinutes = (mins) => {
    if (!mins) return '0m';
    if (mins < 60) return `${Math.round(mins)}m`;
    const h = Math.floor(mins / 60);
    const m = Math.round(mins % 60);
    return `${h}h ${m}m`;
};

const fetchStats = () => {
    const range = document.getElementById('date-range').value;
    const end = new Date().toISOString().split('T')[0];
    const start = new Date(Date.now() - range * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    fetch(`<?= BASE_URL ?>modules/reports/api_stats.php?start=${start}&end=${end}`)
        .then(r => r.json())
        .then(data => {
            // Update top stats
            if (data.operational) {
                document.getElementById('stat-total').textContent = data.operational.total_tickets || '0';
                document.getElementById('stat-ftfr').textContent = data.operational.ftfr_rate + '%';
                document.getElementById('stat-backlog').textContent = data.operational.backlog;
            }
            
            if (data.sla) {
                document.getElementById('stat-compliance').textContent = data.sla.compliance_rate ? `${data.sla.compliance_rate}%` : '0%';
            }
            
            if (data.mttr) {
                document.getElementById('stat-mttr').textContent = formatMinutes(data.mttr.avg_mttr_minutes);
            }

            // Update Chart
            if (data.trends && window.resolutionChart) {
                const labels = data.trends.map(t => t.resolve_date);
                const counts = data.trends.map(t => t.ticket_count);
                if (labels.length === 0) {
                    window.resolutionChart.data.labels = ['No Data'];
                    window.resolutionChart.data.datasets[0].data = [0];
                } else {
                    window.resolutionChart.data.labels = labels;
                    window.resolutionChart.data.datasets[0].data = counts;
                }
                window.resolutionChart.update();
            }

            // Update Hotspots
            const hotspotsContainer = document.getElementById('hotspots-container');
            if (data.hotspots && data.hotspots.length > 0) {
                hotspotsContainer.innerHTML = `<div class="space-y-3">` + data.hotspots.map((h, i) => `
                    <div class="flex items-center justify-between p-3 rounded-lg ${i === 0 ? 'bg-red-50 border border-red-100' : 'bg-gray-50 border border-gray-100'} hover:bg-gray-100 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold ${i === 0 ? 'text-red-700' : 'text-gray-800'} truncate">${h.asset_tag} - ${h.model}</p>
                            <div class="flex items-center gap-2">
                                <p class="text-[10px] ${i === 0 ? 'text-red-500' : 'text-gray-500'} font-medium uppercase">${h.category_name}</p>
                                <span class="text-[9px] text-gray-400 italic">Last: ${new Date(h.last_reported).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pl-3">
                            <span class="text-xs font-bold text-gray-400">TICKETS</span>
                            <span class="text-lg font-extrabold ${i === 0 ? 'text-red-600' : 'text-gray-700'}">${h.ticket_count}</span>
                        </div>
                    </div>
                `).join('') + `</div>`;
            } else {
                hotspotsContainer.innerHTML = '<div class="flex justify-center items-center h-full text-sm text-gray-400 italic">No hotspot data available.</div>';
            }

            // Update Scorecards
            const tbody = document.getElementById('scorecards-tbody');
            if (data.scorecards && data.scorecards.length > 0) {
                tbody.innerHTML = data.scorecards.map(s => `
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="py-3">
                            <p class="font-semibold text-gray-800">${s.full_name}</p>
                            <p class="text-xs text-gray-500">Avg Time: ${formatMinutes(s.avg_labor_time)}</p>
                        </td>
                        <td class="py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#f0fdf4] text-[#166534] border border-green-200">
                                ${s.completed_jobs} / ${s.total_jobs}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <span class="font-bold text-gray-800">${Number(s.avg_rating || 0).toFixed(1)}</span>
                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-400 italic">No scorecard data available.</td></tr>';
            }

            // Update Heatmap
            const heatmapContainer = document.getElementById('heatmap-container');
            if (data.heatmap && data.heatmap.length > 0) {
                heatmapContainer.innerHTML = data.heatmap.map(l => `
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <p class="text-sm font-bold text-gray-800">${l.building}</p>
                            <p class="text-[10px] text-gray-500 uppercase font-medium">Room ${l.room}</p>
                        </div>
                        <span class="text-xs font-extrabold text-[#1a5c2a] bg-green-50 px-2 py-1 rounded border border-green-100">${l.ticket_count} Tickets</span>
                    </div>
                `).join('');
            } else {
                heatmapContainer.innerHTML = '<div class="text-sm text-gray-400 italic text-center py-4">No data.</div>';
            }

            // Update Warranty
            const warrantyContainer = document.getElementById('warranty-container');
            if (data.warranty && data.warranty.length > 0) {
                warrantyContainer.innerHTML = data.warranty.map(w => `
                    <div class="flex items-center justify-between p-2.5 bg-orange-50 rounded-lg border border-orange-100">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-orange-800 truncate">${w.asset_tag}</p>
                            <p class="text-[10px] text-orange-600 font-medium truncate">${w.manufacturer} ${w.model}</p>
                        </div>
                        <div class="text-right pl-2">
                            <p class="text-[10px] font-bold text-orange-700 uppercase">Expiring</p>
                            <p class="text-[10px] text-orange-800 font-mono">${new Date(w.warranty_expiry).toLocaleDateString()}</p>
                        </div>
                    </div>
                `).join('');
            } else {
                warrantyContainer.innerHTML = '<div class="text-sm text-gray-400 italic text-center py-4">No upcoming expirations.</div>';
            }

            // Update Escalations
            const escTbody = document.getElementById('escalations-tbody');
            if (data.escalations && data.escalations.length > 0) {
                escTbody.innerHTML = data.escalations.map(e => `
                    <tr class="hover:bg-red-50 transition-colors cursor-pointer" onclick="window.open('<?= BASE_URL ?>modules/tickets/view.php?id=${e.ticket_id}', '_blank')">
                        <td class="py-2 px-2 font-bold text-red-700">#${e.ticket_number}</td>
                        <td class="py-2 px-2 text-gray-700">${e.assignee || '<span class="italic text-gray-400">Unassigned</span>'}</td>
                        <td class="py-2 px-2">
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-red-100 text-red-800 border border-red-200">
                                ${e.is_resolution_breached == 1 ? 'Resolution' : 'Response'} Breach
                            </span>
                        </td>
                        <td class="py-2 px-2 text-right text-red-600 font-mono">
                            ${new Date(e.deadline).toLocaleDateString([], {month:'short', day:'numeric'})}
                        </td>
                    </tr>
                `).join('');
            } else {
                escTbody.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-gray-400 italic">No active escalations. Excellent!</td></tr>';
            }
        })
        .catch(err => {
            console.error('Failed to fetch stats:', err);
            document.getElementById('hotspots-container').innerHTML = '<div class="text-sm text-red-500">Failed to load data.</div>';
        });
};

let resolutionChart;
document.addEventListener('DOMContentLoaded', () => {
    const green = '#1a5c2a';
    const bgGreen = 'rgba(26, 92, 42, 0.1)';

    const initChart = () => {
        const ctx = document.getElementById('lineChart').getContext('2d');
    window.resolutionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], // Placeholder
                datasets: [
                    { 
                        label: 'Tickets Resolved', 
                        data: [12, 19, 15, 25], // Placeholder
                        borderColor: green, 
                        tension: 0.4, 
                        fill: true, 
                        backgroundColor: bgGreen, 
                        borderWidth: 2,
                        pointBackgroundColor: green,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 13, family: "'Inter', sans-serif" },
                        bodyFont: { size: 13, family: "'Inter', sans-serif" },
                        padding: 10,
                        cornerRadius: 4,
                        displayColors: false
                    }
                }, 
                scales: { 
                    y: { 
                        beginAtZero: true,
                        grid: { color: '#f3f4f6', drawBorder: false },
                        ticks: { 
                            font: { family: "'Inter', sans-serif", size: 11 }, 
                            color: '#9ca3af',
                            stepSize: 1,
                            precision: 0
                        }
                    }, 
                    x: { 
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#9ca3af' }
                    } 
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    };

    window.openDrilldown = (type) => {
        const modal = document.getElementById('drilldown-modal');
        const tbody = document.getElementById('drilldown-tbody');
        const title = document.getElementById('drilldown-title');
        
        modal.classList.remove('hidden');
        tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400 italic">Loading records...</td></tr>';
        
        const titles = {
            'total': 'Total Tickets Created',
            'breaches': 'SLA Breached Tickets',
            'ftfr': 'First-Time Fix Tickets',
            'mttr': 'Resolved Tickets (MTTR Calculation)',
            'resolved': 'Resolution Trends (All Resolved Tickets)',
            'backlog': 'Current Backlog (Open Tickets)'
        };
        title.textContent = titles[type] || 'Ticket Details';

        const range = document.getElementById('date-range').value;
        const end = new Date().toISOString().split('T')[0];
        const start = new Date(Date.now() - range * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

        fetch(`<?= BASE_URL ?>modules/reports/api_stats.php?drilldown=${type}&start=${start}&end=${end}`)
            .then(r => r.json())
            .then(data => {
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400 italic">No records found for this metric.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.map(t => `
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer border-b border-gray-50" onclick="window.open('<?= BASE_URL ?>modules/tickets/view.php?id=${t.ticket_id}', '_blank')">
                        <td class="py-3 px-6 font-medium text-[#1a5c2a]">#${t.ticket_number}</td>
                        <td class="py-3 px-6 text-gray-600">${t.requester || 'System'}</td>
                        <td class="py-3 px-6">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                ${t.priority === 'critical' ? 'bg-red-100 text-red-800 border border-red-200' : 
                                  t.priority === 'high' ? 'bg-orange-100 text-orange-800 border border-orange-200' : 
                                  'bg-green-100 text-[#166534] border border-[#dcfce7]'}">
                                ${t.priority}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-gray-400 font-mono text-[11px]">${new Date(t.created_at).toLocaleDateString()}</td>
                        <td class="py-3 px-6 text-right">
                            <span class="text-[11px] font-bold ${t.resolution_due ? 'text-red-600' : 'text-gray-400 italic'}">
                                ${t.resolution_due ? new Date(t.resolution_due).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : 'No Deadline'}
                            </span>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(err => {
                console.error('Drilldown fetch error:', err);
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-red-500">Error loading drill-down data.</td></tr>';
            });
    };

    initChart();
    fetchStats();
    
    document.getElementById('date-range').addEventListener('change', fetchStats);
});
</script>
