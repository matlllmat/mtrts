<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT u.user_id, u.full_name, u.department_id, d.department_name, r.role_name 
                    FROM users u 
                    LEFT JOIN departments d ON u.department_id = d.department_id
                    LEFT JOIN roles r ON u.role_id = r.role_id
                    WHERE u.user_id = 1 OR u.department_id IS NOT NULL");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
