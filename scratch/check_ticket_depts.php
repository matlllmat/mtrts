<?php
require_once __DIR__ . '/../config/db.php'; 

try {
    $stmt = $pdo->query("
        SELECT 
            t.ticket_id, 
            t.ticket_number,
            t.requester_id, 
            ru.full_name as requester_name,
            ru.department_id as user_dept_id, 
            d.department_name as user_dept_name,
            t.external_dept_from,
            t.asset_id,
            a.department_id as asset_dept_id,
            ad.department_name as asset_dept_name
        FROM tickets t 
        LEFT JOIN users ru ON t.requester_id = ru.user_id 
        LEFT JOIN departments d ON ru.department_id = d.department_id
        LEFT JOIN assets a ON t.asset_id = a.asset_id
        LEFT JOIN departments ad ON a.department_id = ad.department_id
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
