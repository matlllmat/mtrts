<?php
require_once __DIR__ . '/../config/db.php';

echo "Checking schema sync for DB: " . DB_NAME . "\n";
echo "------------------------------------------\n";

function check_table($pdo, $table) {
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function get_column_info($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

$issues = [];

// 1. Check Tables
$tables = ['audit_log', 'asset_audit_log', 'parts_inventory_audit'];
foreach ($tables as $t) {
    if (check_table($pdo, $t)) {
        echo "[OK] Table '$t' exists.\n";
    } else {
        echo "[MISSING] Table '$t' does not exist.\n";
        $issues[] = "Missing table: $t";
    }
}

// 2. Check wo_notes.note_type
$col = get_column_info($pdo, 'wo_notes', 'note_type');
if ($col) {
    $type = $col['Type'];
    echo "[INFO] wo_notes.note_type schema: $type\n";
    if (strpos($type, 'progress') !== false && strpos($type, 'issue') !== false && strpos($type, 'follow_up') !== false) {
        echo "[OK] wo_notes.note_type is updated.\n";
    } else {
        echo "[OUTDATED] wo_notes.note_type is missing new values.\n";
        $issues[] = "Outdated column: wo_notes.note_type";
    }
} else {
    echo "[ERROR] Could not find wo_notes.note_type column.\n";
}

// 3. Check wo_parts_used columns
foreach (['is_preallocated', 'is_consumed'] as $c) {
    if (get_column_info($pdo, 'wo_parts_used', $c)) {
        echo "[OK] wo_parts_used.$c exists.\n";
    } else {
        echo "[MISSING] wo_parts_used.$c does not exist.\n";
        $issues[] = "Missing column: wo_parts_used.$c";
    }
}

echo "------------------------------------------\n";
if (empty($issues)) {
    echo "SUMMARY: Your database IS inline with the latest changes.\n";
} else {
    echo "SUMMARY: Your database is NOT inline. " . count($issues) . " issue(s) found.\n";
}
