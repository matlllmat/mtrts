<?php
// One-time cleanup. Delete this file after running successfully.
// Run while logged in as an admin/technician-module user.

$module = 'technician';
require_once __DIR__ . '/../../../config/auth_only.php';
require_once __DIR__ . '/../../workorders/functions.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $countBefore = (int)$pdo->query("SELECT COUNT(*) FROM wo_media")->fetchColumn();

    $pdo->exec("
        DELETE m1 FROM wo_media m1
        INNER JOIN wo_media m2
          ON m1.wo_id     = m2.wo_id
         AND m1.file_path = m2.file_path
         AND m1.media_id  > m2.media_id
    ");

    $countAfter = (int)$pdo->query("SELECT COUNT(*) FROM wo_media")->fetchColumn();

    $stmt = $pdo->query("
        SELECT wo.wo_id
        FROM work_orders wo
        JOIN tickets t ON t.ticket_id = wo.ticket_id
        WHERE wo.status IN ('resolved','closed')
          AND t.status NOT IN ('resolved','closed')
    ");
    $wo_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $syncErrors = [];
    foreach ($wo_ids as $wo_id) {
        try {
            sync_ticket_with_wo($pdo, (int)$wo_id);
        } catch (Throwable $e) {
            $syncErrors[] = "wo_id={$wo_id}: " . $e->getMessage();
        }
    }

    echo "wo_media rows: {$countBefore} -> {$countAfter} (removed " . ($countBefore - $countAfter) . " duplicate(s))\n";
    echo "Retroactively synced " . count($wo_ids) . " ticket(s) for already-resolved work orders.\n";
    if ($syncErrors) {
        echo "\nSync errors:\n" . implode("\n", $syncErrors) . "\n";
    }
    echo "\nDone. Delete this file now.";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Cleanup failed: " . $e->getMessage();
}
