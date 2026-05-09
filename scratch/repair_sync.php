<?php
/**
 * REPAIR SCRIPT: Fix Work Order to Ticket Synchronization
 * Run this script to synchronize all tickets with their current work orders.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/workorders/functions.php';

echo "Starting synchronization repair...\n";

try {
    $stmt = $pdo->query("SELECT wo_id, wo_number FROM work_orders ORDER BY wo_id ASC");
    $work_orders = $stmt->fetchAll();
    
    $count = 0;
    foreach ($work_orders as $wo) {
        echo "Syncing {$wo['wo_number']} (ID: {$wo['wo_id']})...\n";
        sync_ticket_with_wo($pdo, (int)$wo['wo_id']);
        $count++;
    }
    
    echo "\nSuccess! Synchronized $count work orders with their respective tickets.\n";
} catch (Exception $e) {
    echo "\nError during synchronization: " . $e->getMessage() . "\n";
}
