<?php
/**
 * Real-Life Scenario Seed Script.
 * This script runs config/seed_real_scenario.sql to populate the database
 * with a realistic lifecycle of tickets and work orders.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/plain');

$sql_file = __DIR__ . '/seed_real_scenario.sql';

if (!file_exists($sql_file)) {
    die("Error: seed_real_scenario.sql not found at $sql_file\n");
}

try {
    $sql = file_get_contents($sql_file);
    
    // The SQL file contains multiple statements. PDO exec() can handle them,
    // but some environments prefer split execution. However, for a seed file
    // with SET variables and multiple inserts, exec() is usually okay 
    // IF the driver supports multi-queries (MySQL does).
    
    echo "Running Real-Life Scenario Seed...\n";
    echo "---------------------------------\n";
    
    $pdo->exec($sql);
    
    echo "SUCCESS: Seed data imported successfully.\n";
    echo "\nScenarios created:\n";
    echo "1. CLOSED:  TKT-2026-0001 (Projector flickering) -> WO-2026-0001 (Completed with Parts & Signoff)\n";
    echo "2. ACTIVE:  TKT-2026-0002 (Auditorium Audio)     -> WO-2026-0002 (In Progress)\n";
    echo "3. ON HOLD: TKT-2026-0003 (Display panel crack)  -> WO-2026-0003 (Waiting for Parts)\n";
    echo "\nYou can now log in as:\n";
    echo "- Technician: technician@olfu.edu.ph / 123123123\n";
    echo "- Admin/Staff: itstaff@olfu.edu.ph / 123123123\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
