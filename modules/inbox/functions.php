<?php
// modules/inbox/functions.php — DB helpers for the Email Inbox feature.
// All queries scope to tickets where channel='email'.
// $pdo is provided by guard.php / auth_only.php — never create one here.

function get_unseen_email_count(PDO $pdo): int {
    return (int) $pdo->query("
        SELECT COUNT(*) FROM tickets
        WHERE channel = 'email' AND email_seen_at IS NULL
    ")->fetchColumn();
}

function get_recent_email_inbox(PDO $pdo, int $limit = 10): array {
    $stmt = $pdo->prepare("
        SELECT t.ticket_id, t.ticket_number, t.title, t.description,
               t.created_at, t.email_seen_at, t.external_email_from, t.external_name_from,
               COALESCE(u.full_name, t.external_name_from) AS sender_name,
               COALESCE(u.email,     t.external_email_from) AS sender_email
        FROM tickets t
        LEFT JOIN users u ON t.requester_id = u.user_id
        WHERE t.channel = 'email'
        ORDER BY t.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_email_inbox_page(PDO $pdo, string $q = '', string $filter = 'all', int $page = 1, int $per = 20): array {
    $where = ["t.channel = 'email'"];
    $params = [];

    if ($filter === 'unread') {
        $where[] = "t.email_seen_at IS NULL";
    } elseif ($filter === 'today') {
        $where[] = "DATE(t.created_at) = CURDATE()";
    } elseif ($filter === 'week') {
        $where[] = "t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    }

    if ($q !== '') {
        $kw = '%' . $q . '%';
        $where[] = "(t.title LIKE ? OR t.description LIKE ? OR t.ticket_number LIKE ?
                    OR u.full_name LIKE ? OR u.email LIKE ?
                    OR t.external_email_from LIKE ? OR t.external_name_from LIKE ?)";
        array_push($params, $kw, $kw, $kw, $kw, $kw, $kw, $kw);
    }

    $where_str = implode(' AND ', $where);
    $offset = max(0, ($page - 1) * $per);

    $stmt = $pdo->prepare("
        SELECT t.ticket_id, t.ticket_number, t.title, t.description, t.status,
               t.created_at, t.email_seen_at, t.external_email_from, t.external_name_from,
               COALESCE(u.full_name, t.external_name_from) AS sender_name,
               COALESCE(u.email,     t.external_email_from) AS sender_email
        FROM tickets t
        LEFT JOIN users u ON t.requester_id = u.user_id
        WHERE $where_str
        ORDER BY t.created_at DESC
        LIMIT $per OFFSET $offset
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_email_inbox_page(PDO $pdo, string $q = '', string $filter = 'all'): int {
    $where = ["t.channel = 'email'"];
    $params = [];

    if ($filter === 'unread') {
        $where[] = "t.email_seen_at IS NULL";
    } elseif ($filter === 'today') {
        $where[] = "DATE(t.created_at) = CURDATE()";
    } elseif ($filter === 'week') {
        $where[] = "t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    }

    if ($q !== '') {
        $kw = '%' . $q . '%';
        $where[] = "(t.title LIKE ? OR t.description LIKE ? OR t.ticket_number LIKE ?
                    OR u.full_name LIKE ? OR u.email LIKE ?
                    OR t.external_email_from LIKE ? OR t.external_name_from LIKE ?)";
        array_push($params, $kw, $kw, $kw, $kw, $kw, $kw, $kw);
    }

    $where_str = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets t
        LEFT JOIN users u ON t.requester_id = u.user_id
        WHERE $where_str
    ");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function mark_email_seen(PDO $pdo, int $ticket_id): void {
    $pdo->prepare("
        UPDATE tickets SET email_seen_at = NOW()
        WHERE ticket_id = ? AND channel = 'email' AND email_seen_at IS NULL
    ")->execute([$ticket_id]);
}

function inbox_time_ago(string $datetime): string {
    $diff = (int) ((new DateTime())->getTimestamp() - (new DateTime($datetime))->getTimestamp());
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return (new DateTime($datetime))->format('M j, Y');
}
