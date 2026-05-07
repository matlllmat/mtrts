<?php
require 'config/db.php';
try {
    $pdo->exec('ALTER TABLE tickets ADD COLUMN asset_tag VARCHAR(255) NULL');
} catch (Exception $e) {}
try {
    $pdo->exec('ALTER TABLE tickets ADD COLUMN model VARCHAR(255) NULL');
} catch (Exception $e) {}
try {
    $pdo->exec('ALTER TABLE tickets ADD COLUMN warranty_status VARCHAR(255) NULL');
} catch (Exception $e) {}
echo 'Done';
