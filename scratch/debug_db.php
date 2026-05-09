<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT wo_id, wo_number, ticket_id, status, assigned_to FROM work_orders WHERE wo_number = 'WO-2026-0007'");
$wo = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Work Order: " . json_encode($wo) . "\n";

if ($wo && $wo['ticket_id']) {
    $stmt = $pdo->prepare("SELECT ticket_id, ticket_number, status, assigned_to FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$wo['ticket_id']]);
    $tk = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Ticket: " . json_encode($tk) . "\n";
}
