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

<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <div onclick="openDrilldown('total')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Total Tickets</p>
        <div class="flex items-end justify-between">
            <span id="stat-total" class="text-3xl font-extrabold text-[#1a5c2a] leading-none">-</span>
        </div>
    </div>
    <div onclick="openDrilldown('breaches')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">SLA Compliance</p>
        <div class="flex items-end justify-between">
            <span id="stat-compliance" class="text-3xl font-extrabold text-[#1a5c2a] leading-none">-</span>
        </div>
    </div>
    <div onclick="openDrilldown('mttr')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Avg MTTR</p>
        <span id="stat-mttr" class="text-2xl font-extrabold text-gray-800 leading-none">-</span>
    </div>
    <div onclick="openDrilldown('ftfr')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-[#1a5c2a] transition-all">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">FTFR</p>
        <span id="stat-ftfr" class="text-2xl font-extrabold text-gray-800 leading-none">-</span>
    </div>
    <div onclick="openDrilldown('backlog')" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-full cursor-pointer hover:shadow-md hover:border-red-500 transition-all">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Backlog</p>
        <span id="stat-backlog" class="text-2xl font-extrabold text-red-600 leading-none">-</span>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border-t-4 border-t-yellow-400 border-x-gray-100 border-b-gray-100 flex flex-col justify-between h-full">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Audit Status</p>
        <span class="text-lg font-extrabold text-[#1a5c2a] leading-none flex items-center gap-1">
            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Secure
        </span>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 min-h-[320px] flex flex-col xl:col-span-2">
        <h3 class="font-bold text-gray-800 mb-4 text-base">Resolution Trends</h3>
        <div class="flex-1 relative w-full h-full min-h-[240px]">
            <canvas id="lineChart"></canvas>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 min-h-[320px] flex flex-col">
        <h3 class="font-bold text-gray-800 mb-4 text-base">Failure Hotspots</h3>
        <div class="flex-1 overflow-y-auto pr-2" id="hotspots-container">
            <div class="flex justify-center items-center h-full text-sm text-gray-400 italic">Loading...</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 text-base">Technician Scorecards</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Technician</th>
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Completed</th>
                        <th class="py-2 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Avg Rating</th>
                    </tr>
                </thead>
                <tbody id="scorecards-tbody" class="divide-y divide-gray-50 text-sm">
                    <tr><td colspan="3" class="py-4 text-center text-gray-400 italic">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-[#f0fdf4] rounded-xl p-5 shadow-sm border border-[#dcfce7]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-[#166534] text-base">System Auditing</h3>
            <span class="bg-[#dcfce7] text-[#166534] text-[10px] font-bold px-2 py-1 rounded tracking-wider uppercase border border-green-200">Active</span>
        </div>
        <p class="text-sm text-gray-600 mb-4">The API audit engine is actively logging all record modifications. Personal identifiable information (PII) is automatically masked for non-administrators.</p>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-auto">
            <button onclick="window.location.href='<?= BASE_URL ?>modules/reports/export.php'" class="flex items-center justify-center gap-2 text-sm font-semibold text-[#1a5c2a] bg-white px-4 py-2.5 rounded-lg shadow-sm border border-[#dcfce7] hover:bg-green-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Generate Report
            </button>
            <a href="<?= BASE_URL ?>modules/reports/audit.php" class="flex items-center justify-center gap-2 text-sm font-semibold text-white bg-[#1a5c2a] px-4 py-2.5 rounded-lg shadow-sm hover:bg-[#1f6e32] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                View Audit Logs
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
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Status</th>
                        <th class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Created</th>
                    </tr>
                </thead>
                <tbody id="drilldown-tbody" class="divide-y divide-gray-50 text-sm">
                    <tr><td colspan="5" class="py-8 text-center text-gray-400 italic">Loading records...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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

            // Update Hotspots
            const hotspotsContainer = document.getElementById('hotspots-container');
            if (data.hotspots && data.hotspots.length > 0) {
                hotspotsContainer.innerHTML = `<div class="space-y-3">` + data.hotspots.map((h, i) => `
                    <div class="flex items-center justify-between p-3 rounded-lg ${i === 0 ? 'bg-red-50 border border-red-100' : 'bg-gray-50 border border-gray-100'} hover:bg-gray-100 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold ${i === 0 ? 'text-red-700' : 'text-gray-800'} truncate">${h.asset_tag} - ${h.model}</p>
                            <p class="text-xs ${i === 0 ? 'text-red-500' : 'text-gray-500'}">${h.category_name}</p>
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
        resolutionChart = new Chart(ctx, {
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
                        ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#9ca3af' }
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
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.open('<?= BASE_URL ?>modules/tickets/view.php?id=${t.ticket_id}', '_blank')">
                        <td class="py-3 px-6 font-medium text-[#1a5c2a]">${t.ticket_number}</td>
                        <td class="py-3 px-6 text-gray-600">${t.requester || 'System'}</td>
                        <td class="py-3 px-6">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                ${t.priority === 'critical' ? 'bg-red-100 text-red-800 border border-red-200' : 
                                  t.priority === 'high' ? 'bg-orange-100 text-orange-800 border border-orange-200' : 
                                  'bg-green-100 text-[#166534] border border-[#dcfce7]'}">
                                ${t.priority}
                            </span>
                        </td>
                        <td class="py-3 px-6">
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-[10px] font-bold uppercase tracking-wider border border-gray-200">
                                ${t.status.replace('_', ' ')}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-gray-500 whitespace-nowrap">${t.created_at.split(' ')[0]}</td>
                    </tr>
                `).join('');
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-red-500">Error loading drill-down data.</td></tr>';
            });
    };

    initChart();
    fetchStats();
    
    document.getElementById('date-range').addEventListener('change', fetchStats);
});
</script>
