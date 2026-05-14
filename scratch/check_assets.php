<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT a.asset_id, a.asset_tag, a.department_id, d.department_name 
                    FROM assets a 
                    LEFT JOIN departments d ON a.department_id = d.department_id 
                    WHERE a.department_id IS NOT NULL");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
