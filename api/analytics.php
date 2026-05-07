<?php
// api/analytics.php
// REST BI Connector for external reporting tools (PowerBI, Tableau)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/reports/functions.php';

header('Access-Control-Allow-Origin: *'); // Allow external BI tools

// Security: Check for API Key or Session
// For simplicity, we'll check session, but in production, use a Bearer token
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 8])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized. BI access requires Administrator or Manager privileges.']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-90 days'));
$end = $_GET['end'] ?? date('Y-m-d');

$data = [
    'metadata' => [
        'generated_at' => date('c'),
        'range' => [$start, $end],
        'version' => '1.0'
    ],
    'sla_summary' => get_sla_compliance_stats($pdo, $start, $end),
    'operational_metrics' => get_operational_stats($pdo, $start, $end),
    'mttr_data' => get_mttr_stats($pdo, $start, $end),
    'asset_hotspots' => get_asset_hotspots($pdo, 20),
    'location_heatmap' => get_location_heatmap($pdo),
    'technician_performance' => get_technician_scorecards($pdo)
];

$is_browser = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false;
$wants_json = isset($_GET['format']) && $_GET['format'] === 'json';

if (!$is_browser || $wants_json) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REST BI Connector - Data Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 p-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-3xl font-black text-[#1a5c2a] tracking-tight">REST BI Connector</h1>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-sm text-gray-500 font-medium">Data Gateway for External Analytics</p>
                    <div class="relative group/info">
                        <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="absolute left-0 top-full mt-2 w-80 p-4 bg-gray-900 text-white text-xs rounded-xl shadow-2xl opacity-0 group-hover/info:opacity-100 transition-opacity pointer-events-none z-50 leading-relaxed border border-gray-700">
                            <p class="font-bold text-green-400 mb-2">How to use this page:</p>
                            <p class="mb-3">This page is a <b>Data Connector</b>. You can copy the URL of this page and paste it into PowerBI (Get Data -> Web) or Tableau (Web Data Connector) to sync your live MTRTS data.</p>
                            <p class="font-bold text-orange-400 mb-1 italic">Security Protocol:</p>
                            <p>This endpoint requires an active session. For automated tools, use the "View Raw JSON" button to get the machine-readable payload.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="../modules/reports/index.php" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                    Back to Dashboard
                </a>
                <a href="?format=json" class="bg-[#1a5c2a] hover:bg-[#1f6e32] text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg transition-all flex items-center gap-2 hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    View Raw JSON
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Metadata -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative group/meta">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Metadata</h3>
                    <svg class="w-3.5 h-3.5 text-gray-300 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="absolute right-6 top-12 w-64 p-3 bg-gray-800 text-white text-[10px] rounded-xl shadow-xl opacity-0 group-hover/meta:opacity-100 transition-opacity pointer-events-none z-50">
                        <b>What is this?</b> Metadata helps your reporting tool understand when the data was last pulled and which filters were applied.
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-xs font-medium text-gray-500">Generated At</span>
                        <span class="text-xs font-bold text-gray-800"><?= date('M j, Y H:i:s', strtotime($data['metadata']['generated_at'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs font-medium text-gray-500">Date Range</span>
                        <span class="text-xs font-bold text-gray-800"><?= $start ?> - <?= $end ?></span>
                    </div>
                </div>
            </div>

            <!-- SLA Summary -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative group/sla">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">SLA Insights</h3>
                    <svg class="w-3.5 h-3.5 text-gray-300 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="absolute right-6 top-12 w-64 p-3 bg-gray-800 text-white text-[10px] rounded-xl shadow-xl opacity-0 group-hover/sla:opacity-100 transition-opacity pointer-events-none z-50">
                        <b>BI Power:</b> These pre-aggregated numbers allow PowerBI to build high-level cards without doing heavy calculations.
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Compliance</p>
                        <p class="text-xl font-black text-[#1a5c2a]"><?= $data['sla_summary']['compliance_rate'] ?? 0 ?>%</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">MTTR</p>
                        <p class="text-xl font-black text-gray-800"><?= $data['mttr_data']['avg_mttr_hours'] ?? 0 ?>h</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-900 rounded-[2rem] shadow-2xl overflow-hidden border border-gray-800 relative group/code">
            <div class="bg-gray-800/50 px-8 py-5 border-b border-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-[#ff5f56]"></div>
                        <div class="w-3 h-3 rounded-full bg-[#ffbd2e]"></div>
                        <div class="w-3 h-3 rounded-full bg-[#27c93f]"></div>
                    </div>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Secure JSON Payload Preview</span>
                </div>
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-500 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="absolute right-0 top-full mt-2 w-64 p-3 bg-gray-700 text-white text-[10px] rounded-xl shadow-xl opacity-0 group-hover/code:opacity-100 transition-opacity pointer-events-none z-50">
                        <b>Developer Note:</b> This is the machine-readable version of your database. External tools consume this raw code to build custom charts.
                    </div>
                </div>
            </div>
            <div class="p-8 overflow-auto max-h-[500px] custom-scrollbar">
                <pre class="text-[11px] font-mono text-green-400 leading-relaxed selection:bg-green-900 selection:text-white"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em]">MTRTS Open-Data Connector API • v1.0</p>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #111827; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #374151; }
    </style>
    </div>
</body>
</html>
