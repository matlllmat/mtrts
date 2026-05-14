<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("DESCRIBE tickets");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . "\n";
}
