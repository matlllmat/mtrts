<?php
// modules/reports/cron_data_retention.php
// Scheduled task to enforce Data Retention Policies.
// Goal: PII minimization for historical records (> 2 years old).

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/functions.php';

echo "--- DATA RETENTION WORKER START ---\n";

// Policy: Mask PII for tickets closed more than 2 years ago.
$cutoff = date('Y-m-d H:i:s', strtotime('-2 years'));

try {
    $pdo->beginTransaction();

    // 1. Mask PII values in audit logs for records older than the cutoff.
    // Iterate so we actually overwrite values, not just rename keys.
    $sel = $pdo->prepare("
        SELECT log_id, old_values, new_values FROM audit_log
        WHERE created_at < ?
          AND (old_values LIKE '%@%' OR new_values LIKE '%@%' OR old_values LIKE '%contact_number%' OR new_values LIKE '%contact_number%')
    ");
    $sel->execute([$cutoff]);

    $upd = $pdo->prepare("UPDATE audit_log SET old_values = ?, new_values = ? WHERE log_id = ?");

    $mask_fn = function (?string $json): ?string {
        if (!$json) return $json;
        $masked = preg_replace('/"email"\s*:\s*"[^"]*"/i', '"email":"***@***.***"', $json);
        $masked = preg_replace('/"contact_number"\s*:\s*"[^"]*"/i', '"contact_number":"***-***-****"', $masked);
        $masked = preg_replace('/"full_name"\s*:\s*"[^"]*"/i', '"full_name":"***MASKED***"', $masked);
        return $masked;
    };

    $affected = 0;
    foreach ($sel as $row) {
        $new_old = $mask_fn($row['old_values']);
        $new_new = $mask_fn($row['new_values']);
        if ($new_old !== $row['old_values'] || $new_new !== $row['new_values']) {
            $upd->execute([$new_old, $new_new, $row['log_id']]);
            $affected++;
        }
    }
    echo "Audit logs minimized: $affected records.\n";

    // 2. Anonymize very old tickets (e.g. > 5 years)
    $extreme_cutoff = date('Y-m-d H:i:s', strtotime('-5 years'));
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET description = '[DATA EXPIRED - RETENTION POLICY]',
            title = CONCAT('Archive: ', ticket_number)
        WHERE created_at < ? AND status IN ('closed', 'cancelled')
    ");
    $stmt->execute([$extreme_cutoff]);
    $affected_tickets = $stmt->rowCount();
    echo "Archived tickets: $affected_tickets records.\n";

    $pdo->commit();
    echo "--- DATA RETENTION WORKER COMPLETE ---\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
