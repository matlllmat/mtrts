<?php
$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';

$cats  = $pdo->query("SELECT category_name FROM asset_categories ORDER BY category_id LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
$locs  = $pdo->query("SELECT building, floor, room FROM locations ORDER BY location_id LIMIT 3")->fetchAll();
$depts = $pdo->query("SELECT department_name FROM departments ORDER BY department_id LIMIT 1")->fetchAll(PDO::FETCH_COLUMN);
$dept  = $depts[0] ?? '';

$today = new DateTimeImmutable();
$rows  = [];
$letters = ['A', 'B', 'C'];

for ($i = 0; $i < min(3, max(count($cats), 1)); $i++) {
    $cat = $cats[$i] ?? ($cats[0] ?? 'General');
    $loc = $locs[$i]  ?? ($locs[0]  ?? null);
    $tag     = 'DEMO-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT) . '-' . $letters[$i];
    $serial  = 'DEMO-SN-' . str_pad(1000 + $i, 5, '0', STR_PAD_LEFT);
    $install = $today->modify('-' . (12 + $i * 6) . ' months')->format('Y-m-d');
    $w_start = $install;
    $w_end   = $today->modify('+' . (24 - $i * 6) . ' months')->format('Y-m-d');
    $rows[]  = [
        $tag, $serial, 'Demo Manufacturer', 'Demo Model ' . $letters[$i],
        $cat, 'active', $install,
        $loc['building'] ?? '', $loc['floor'] ?? '', $loc['room'] ?? '',
        '', $dept, '', '', '',
        $w_start, $w_end, 'parts_and_labor', 'Demo Vendor', 'DEMO-CONTRACT-' . ($i + 1),
    ];
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="sample_asset_import.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$out = fopen('php://output', 'w');
fputcsv($out, [
    'asset_tag','serial_number','manufacturer','model','category','status','install_date',
    'building','floor','room','owner','department','firmware_version','network_info','bulb_hours',
    'warranty_start','warranty_end','coverage_type','vendor_name','contract_reference',
]);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
