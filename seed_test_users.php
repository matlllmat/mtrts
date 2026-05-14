<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/modules/users/functions.php';

$roles = $pdo->query("SELECT role_id, role_name FROM roles")->fetchAll();
$pass = '123123123';

echo "Creating 2 users for each role...\n";

foreach ($roles as $role) {
    $role_id = $role['role_id'];
    $role_name = $role['role_name'];
    
    for ($i = 1; $i <= 2; $i++) {
        $email = "test.{$role_name}.{$i}@example.com";
        $full_name = "Test " . ucwords(str_replace('_', ' ', $role_name)) . " " . $i;
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "User {$email} already exists. Skipping.\n";
            continue;
        }
        
        $d = [
            'email'          => $email,
            'password'       => $pass,
            'full_name'      => $full_name,
            'id_number'      => "ID-" . strtoupper($role_name) . "-" . $i . "-" . rand(1000, 9999),
            'contact_number' => '0917' . rand(1000000, 9999999),
            'position'       => ucwords(str_replace('_', ' ', $role_name)),
            'department_id'  => 1, // IT Department
            'role_id'        => $role_id
        ];
        
        try {
            create_user($pdo, $d);
            echo "Created: {$email}\n";
        } catch (Exception $e) {
            echo "Error creating {$email}: " . $e->getMessage() . "\n";
        }
    }
}
echo "Done.\n";
