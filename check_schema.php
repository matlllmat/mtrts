<?php
require_once __DIR__ . '/config/db.php';

echo "--- Checking tickets table ---\n";
try {
    $stmt = $pdo->query("DESCRIBE tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in 'tickets': " . implode(", ", $columns) . "\n";
} catch (Exception $e) {
    echo "Error describing tickets: " . $e->getMessage() . "\n";
}

echo "\n--- Checking ticket_categories table ---\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ticket_categories'");
    $table = $stmt->fetch();
    echo "Table 'ticket_categories' exists: " . ($table ? "Yes" : "No") . "\n";
} catch (Exception $e) {
    echo "Error checking ticket_categories: " . $e->getMessage() . "\n";
}

echo "\n--- Checking asset_categories table ---\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'asset_categories'");
    $table = $stmt->fetch();
    echo "Table 'asset_categories' exists: " . ($table ? "Yes" : "No") . "\n";
} catch (Exception $e) {
    echo "Error checking asset_categories: " . $e->getMessage() . "\n";
}
