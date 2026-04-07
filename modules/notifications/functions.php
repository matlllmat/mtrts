<?php
// modules/notifications/functions.php
// All database queries and helpers for the Notifications module.
// $pdo is provided by guard.php / auth_only.php — never create one here.

// ── Write ─────────────────────────────────────────────────────

/**
 * Insert a notification for one user.
 * $notif_key prevents duplicates for automated alerts — pass null for ad-hoc.
 * If the same (notif_key, user_id) pair already exists, the INSERT is silently
 * ignored thanks to the UNIQUE KEY + INSERT IGNORE.
 */
function notify_user(
    PDO $pdo,
    int $user_id,
    string $title,
    string $body,
    string $link = '',
    ?string $notif_key = null
): void {
    $pdo->prepare("
        INSERT IGNORE INTO notifications (user_id, title, body, link, notif_key)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$user_id, $title, $body ?: null, $link ?: null, $notif_key]);
}

// ── Read ──────────────────────────────────────────────────────

function get_unread_count(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returns the N most recent notifications for the dropdown.
 */
function get_recent_notifications(PDO $pdo, int $user_id, int $limit = 10): array {
    $stmt = $pdo->prepare("
        SELECT notif_id, title, body, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Returns a paginated list of all notifications for the full-page view.
 */
function get_all_notifications(PDO $pdo, int $user_id, int $page = 1, int $per = 20): array {
    $offset = ($page - 1) * $per;
    $stmt = $pdo->prepare("
        SELECT notif_id, title, body, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $per, $offset]);
    return $stmt->fetchAll();
}

function count_all_notifications(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

// ── Mark read ─────────────────────────────────────────────────

function mark_notification_read(PDO $pdo, int $notif_id, int $user_id): void {
    $pdo->prepare(
        "UPDATE notifications SET is_read = 1 WHERE notif_id = ? AND user_id = ?"
    )->execute([$notif_id, $user_id]);
}

function mark_all_notifications_read(PDO $pdo, int $user_id): void {
    $pdo->prepare(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
    )->execute([$user_id]);
}

// ── Warranty expiry check ─────────────────────────────────────

/**
 * Scans asset_warranty for upcoming expirations and fires notifications
 * for admin / it_manager / it_staff / super_admin (role_ids 1, 2, 3, 8).
 *
 * Alert thresholds: 60, 30, 7 days before warranty_end.
 * Each asset fires only the MOST SPECIFIC (smallest) applicable threshold.
 * notif_key format: warranty_{asset_id}_{warranty_end}_{threshold}
 * — guarantees each threshold fires exactly once per asset, even across
 *   multiple page loads.
 *
 * Call this on page load, throttled to once per hour via $_SESSION.
 */
function check_warranty_expiry(PDO $pdo): void {
    // Assets expiring within 60 days, not yet expired
    $assets = $pdo->query("
        SELECT a.asset_id, a.asset_tag, a.manufacturer, a.model,
               w.warranty_end,
               DATEDIFF(w.warranty_end, CURDATE()) AS days_left
        FROM asset_warranty w
        JOIN assets a ON a.asset_id = w.asset_id
        WHERE w.warranty_end >= CURDATE()
          AND w.warranty_end <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ")->fetchAll();

    if (!$assets) return;

    $recipients = $pdo->query("
        SELECT user_id FROM users
        WHERE role_id IN (1, 2, 3, 8) AND is_active = 1
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!$recipients) return;

    $thresholds = [
        7  => '7-Day Warranty Warning',
        30 => '30-Day Warranty Notice',
        60 => '60-Day Warranty Notice',
    ];

    foreach ($assets as $asset) {
        $days = (int) $asset['days_left'];
        $end  = $asset['warranty_end'];

        foreach ($thresholds as $t => $title) {
            if ($days > $t) continue;

            $notif_key = "warranty_{$asset['asset_id']}_{$end}_{$t}";
            $body = "{$asset['manufacturer']} {$asset['model']} ({$asset['asset_tag']}) "
                  . "— warranty expires on {$end}.";
            $link = BASE_URL . "modules/assets/view.php?id={$asset['asset_id']}";

            foreach ($recipients as $user_id) {
                notify_user($pdo, (int) $user_id, $title, $body, $link, $notif_key);
            }

            break; // Only fire the most specific threshold
        }
    }
}

// ── Render helpers ────────────────────────────────────────────

function notif_time_ago(string $datetime): string {
    $diff = (int) ((new DateTime())->getTimestamp() - (new DateTime($datetime))->getTimestamp());
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return (new DateTime($datetime))->format('M j, Y');
}
