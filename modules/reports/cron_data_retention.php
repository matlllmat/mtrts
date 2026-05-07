<?php
// modules/reports/cron_data_retention.php
// ─────────────────────────────────────────────────────
// Data Retention Policy Enforcement
// Run via CRON monthly: php C:\xampp\htdocs\mtrts\modules\reports\cron_data_retention.php
//
// Policies:
//   - Audit logs older than 2 years → Archived then purged
//   - Notifications older than 90 days (read) → Deleted
//   - Offline sync queue (completed) older than 30 days → Deleted
//   - PII in closed tickets older than 1 year → Minimized
// ─────────────────────────────────────────────────────

if (!defined('BASE_URL')) define('BASE_URL', '/mtrts/');

require_once __DIR__ . '/../../config/db.php';

echo "[DATA RETENTION] Starting at " . date('Y-m-d H:i:s') . "\n";

// ── 1. Archive and purge old audit logs (>2 years) ──
$cutoff_audit = date('Y-m-d', strtotime('-2 years'));
$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE created_at < ?");
$stmt->execute([$cutoff_audit]);
$old_audit = (int)$stmt->fetchColumn();

if ($old_audit > 0) {
    // Export before purge (compliance archive)
    $archive_dir = __DIR__ . '/../../public/archives/';
    if (!is_dir($archive_dir)) mkdir($archive_dir, 0777, true);
    
    $archive_file = $archive_dir . 'audit_archive_' . date('Y-m-d') . '.csv';
    $fp = fopen($archive_file, 'w');
    fputcsv($fp, ['log_id', 'user_id', 'action', 'object_type', 'object_id', 'old_values', 'new_values', 'ip_address', 'created_at']);
    
    $stmt = $pdo->prepare("SELECT * FROM audit_log WHERE created_at < ?");
    $stmt->execute([$cutoff_audit]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($fp, array_values($row));
    }
    fclose($fp);
    
    // Now purge
    $pdo->prepare("DELETE FROM audit_log WHERE created_at < ?")->execute([$cutoff_audit]);
    echo "[DATA RETENTION] Archived and purged {$old_audit} audit logs older than {$cutoff_audit}\n";
} else {
    echo "[DATA RETENTION] No audit logs to archive.\n";
}

// ── 2. Clear read notifications older than 90 days ──
$cutoff_notif = date('Y-m-d', strtotime('-90 days'));
$stmt = $pdo->prepare("DELETE FROM notifications WHERE is_read = 1 AND created_at < ?");
$stmt->execute([$cutoff_notif]);
$notif_deleted = $stmt->rowCount();
echo "[DATA RETENTION] Purged {$notif_deleted} read notifications older than 90 days.\n";

// ── 3. Clear completed sync queue older than 30 days ──
$cutoff_sync = date('Y-m-d', strtotime('-30 days'));
$stmt = $pdo->prepare("DELETE FROM offline_sync_queue WHERE sync_status = 'completed' AND synced_at < ?");
$stmt->execute([$cutoff_sync]);
$sync_deleted = $stmt->rowCount();
echo "[DATA RETENTION] Purged {$sync_deleted} completed sync records older than 30 days.\n";

// ── 4. PII minimization for closed tickets older than 1 year ──
$cutoff_pii = date('Y-m-d', strtotime('-1 year'));
$stmt = $pdo->prepare("
    UPDATE ticket_comments tc
    JOIN tickets t ON tc.ticket_id = t.ticket_id
    SET tc.comment_text = '[Content redacted — data retention policy]'
    WHERE t.status IN ('closed', 'cancelled')
      AND t.closed_at < ?
      AND tc.comment_text NOT LIKE '%[Content redacted%'
");
$stmt->execute([$cutoff_pii]);
$comments_redacted = $stmt->rowCount();
echo "[DATA RETENTION] Redacted {$comments_redacted} comments from closed tickets older than 1 year.\n";

// ── 5. Log this retention run to audit_log ──
$pdo->prepare("
    INSERT INTO audit_log (user_id, action, object_type, object_id, new_values, ip_address, created_at)
    VALUES (NULL, 'RETENTION', 'system', NULL, ?, 'CLI', NOW())
")->execute([json_encode([
    'audit_archived'      => $old_audit,
    'notifications_purged' => $notif_deleted,
    'sync_queue_purged'    => $sync_deleted,
    'comments_redacted'    => $comments_redacted,
    'run_date'             => date('Y-m-d H:i:s')
])]);

echo "[DATA RETENTION] Completed at " . date('Y-m-d H:i:s') . "\n";
