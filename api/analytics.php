<?php
// api/analytics.php
// REST BI Connector for external reporting tools (PowerBI, Tableau)

<<<<<<< HEAD
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/reports/functions.php';

header('Content-Type: application/json');
=======
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/reports/functions.php';

>>>>>>> 0b371872eff460cdb0693a941470db1c568d6a04
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

<<<<<<< HEAD
echo json_encode($data, JSON_PRETTY_PRINT);
=======
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
        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-2xl font-extrabold text-[#1a5c2a]">REST BI Connector Data</h1>
                <p class="text-sm text-gray-500 mt-1">This is a human-readable preview of the data sent to PowerBI / Tableau.</p>
            </div>
            <a href="?format=json" class="bg-[#1a5c2a] hover:bg-[#1f6e32] text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                View Raw JSON
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Metadata -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 border-b pb-2 mb-3">Metadata</h3>
                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold text-gray-500">Generated At:</span> <?= $data['metadata']['generated_at'] ?></p>
                    <p><span class="font-semibold text-gray-500">Date Range:</span> <?= $start ?> to <?= $end ?></p>
                    <p><span class="font-semibold text-gray-500">API Version:</span> <?= $data['metadata']['version'] ?></p>
                </div>
            </div>

            <!-- SLA Summary -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 border-b pb-2 mb-3">SLA Summary</h3>
                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold text-gray-500">Total Tickets:</span> <?= $data['sla_summary']['total_tickets'] ?? 0 ?></p>
                    <p><span class="font-semibold text-gray-500">Met Response SLA:</span> <?= $data['sla_summary']['met_response'] ?? 0 ?></p>
                    <p><span class="font-semibold text-gray-500">Met Resolution SLA:</span> <?= $data['sla_summary']['met_resolution'] ?? 0 ?></p>
                    <p><span class="font-semibold text-gray-500">Compliance Rate:</span> <span class="text-[#1a5c2a] font-bold"><?= $data['sla_summary']['compliance_rate'] ?? 0 ?>%</span></p>
                </div>
            </div>
        </div>

        <div class="bg-gray-900 rounded-xl shadow-md overflow-hidden">
            <div class="bg-gray-800 px-4 py-3 flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Raw Payload Data</span>
                <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
            </div>
            <div class="p-4 overflow-auto max-h-[600px]">
                <pre class="text-xs font-mono text-green-400 leading-relaxed"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
>>>>>>> 0b371872eff460cdb0693a941470db1c568d6a04
