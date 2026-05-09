<?php
$pdo = new PDO('mysql:host=localhost;dbname=mtrts_sql', 'root', '');
$stats = [];

// 1. Warranty Exposure (Next 90 days)
$stats['warranty_check'] = $pdo->query("
    SELECT COUNT(*) 
    FROM asset_warranty 
    WHERE warranty_end >= CURDATE() 
      AND warranty_end <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
")->fetchColumn();

// 2. Ticket Aging
$stats['aging_raw'] = $pdo->query("
    SELECT 
        SUM(DATEDIFF(NOW(), created_at) BETWEEN 0 AND 7) as bucket_0_7,
        SUM(DATEDIFF(NOW(), created_at) BETWEEN 8 AND 14) as bucket_8_14,
        SUM(DATEDIFF(NOW(), created_at) > 14) as bucket_older,
        COUNT(*) as total_open
    FROM tickets 
    WHERE status NOT IN ('resolved', 'closed', 'cancelled')
")->fetch(PDO::FETCH_ASSOC);

// 3. Cost Analytics (Parts)
$stats['total_parts_in_db'] = $pdo->query("
    SELECT SUM(pu.quantity_used * pi.unit_cost) 
    FROM wo_parts_used pu
    JOIN parts_inventory pi ON pu.part_id = pi.part_id
")->fetchColumn();

// 4. Location Heatmap
$stats['all_locations'] = $pdo->query("
    SELECT l.building, l.room, COUNT(t.ticket_id) as count
    FROM tickets t
    JOIN locations l ON t.location_id = l.location_id
    GROUP BY l.location_id
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($stats, JSON_PRETTY_PRINT);
