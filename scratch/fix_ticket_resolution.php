<?php
require_once __DIR__ . '/../config/db.php';

echo "Starting ticket status sync...\n";

// Find tickets that have at least one resolved/closed work order but are not marked as resolved
$stmt = $pdo->query("
    SELECT t.ticket_id, t.ticket_number, wo.actual_end, wo.wo_number
    FROM tickets t
    JOIN work_orders wo ON t.ticket_id = wo.ticket_id
    WHERE wo.status IN ('resolved', 'closed')
      AND t.status NOT IN ('resolved', 'closed')
");

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Updating Ticket #{$row['ticket_number']} (from WO {$row['wo_number']})\n";
    
    $update = $pdo->prepare("
        UPDATE tickets 
        SET status = 'resolved', 
            resolved_at = COALESCE(?, NOW()),
            updated_at = NOW() 
        WHERE ticket_id = ?
    ");
    $update->execute([$row['actual_end'], $row['ticket_id']]);
    
    // Also sync SLA
    $pdo->prepare("
        UPDATE ticket_sla 
        SET resolved_at = COALESCE(?, NOW()) 
        WHERE ticket_id = ?
    ")->execute([$row['actual_end'], $row['ticket_id']]);
    
    $count++;
}

echo "Finished. Updated $count tickets.\n";
