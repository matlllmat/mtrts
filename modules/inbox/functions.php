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

// ─── Inbox Messages helpers (technician-customer-messaging feature) ───────────

/**
 * Insert a new inbox_messages row.
 *
 * Validates that both $sender and $recipient correspond to is_active = 1 users.
 * Returns the new message_id (> 0) on success, or 0 on any validation/DB failure.
 * Never throws — all exceptions are caught internally.
 *
 * Requirements: 3.1, 3.2, 3.3, 3.6
 */
function insert_inbox_message(
    PDO $pdo,
    int $sender,
    int $recipient,
    string $subject,
    string $body,
    ?int $wo_id,
    ?int $ticket_id
): int {
    try {
        // Validate sender is an active user
        $chk = $pdo->prepare(
            "SELECT user_id FROM users WHERE user_id = ? AND is_active = 1"
        );
        $chk->execute([$sender]);
        if (!$chk->fetch()) {
            return 0;
        }

        // Validate recipient is an active user
        $chk->execute([$recipient]);
        if (!$chk->fetch()) {
            return 0;
        }

        // Insert the message; sent_at defaults to CURRENT_TIMESTAMP, read_at defaults to NULL
        $stmt = $pdo->prepare("
            INSERT INTO inbox_messages
                (sender_id, recipient_id, subject, body, wo_id, ticket_id)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sender,
            $recipient,
            $subject,
            $body,
            $wo_id,
            $ticket_id,
        ]);

        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Count unread inbox_messages for the given user.
 *
 * Returns the number of rows in inbox_messages where recipient_id = $user_id
 * and read_at IS NULL.
 *
 * Requirements: 2.2, 2.3
 */
function get_unread_inbox_count(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM inbox_messages WHERE recipient_id = ? AND read_at IS NULL"
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Mark an inbox message as read for the given user.
 *
 * Returns false immediately if the message's recipient_id does not match $user_id.
 * Returns true without issuing an UPDATE if read_at is already non-NULL (idempotent).
 * Otherwise updates read_at to NOW() and returns true.
 *
 * Requirements: 3.4, 3.5, 8.1, 8.2, 8.3
 */
function mark_inbox_message_read(PDO $pdo, int $message_id, int $user_id): bool {
    // Fetch the message row to check ownership and current read state
    $stmt = $pdo->prepare(
        "SELECT recipient_id, read_at FROM inbox_messages WHERE message_id = ?"
    );
    $stmt->execute([$message_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Message not found — cannot mark as read
    if ($row === false) {
        return false;
    }

    // Ownership check: recipient must match the requesting user
    if ((int)$row['recipient_id'] !== $user_id) {
        return false;
    }

    // Already read — return true without issuing an UPDATE (idempotent)
    if ($row['read_at'] !== null) {
        return true;
    }

    // Mark as read: only update rows that are still unread (race-condition safe)
    $update = $pdo->prepare(
        "UPDATE inbox_messages SET read_at = NOW() WHERE message_id = ? AND recipient_id = ? AND read_at IS NULL"
    );
    $update->execute([$message_id, $user_id]);

    return true;
}

/**
 * Return the N most recent inbox_messages for the given recipient,
 * ordered by sent_at DESC. JOINs to users on sender_id to include
 * the sender's full name.
 *
 * Returns rows with: message_id, sender_id, sender_name, subject,
 * preview (first 100 chars of body), sent_at, read_at.
 *
 * Validates: Requirements 2.2, 2.6
 */
function get_recent_inbox_messages(PDO $pdo, int $user_id, int $limit = 10): array {
    $stmt = $pdo->prepare("
        SELECT
            m.message_id,
            m.sender_id,
            u.full_name                          AS sender_name,
            m.subject,
            LEFT(m.body, 100)                    AS preview,
            m.sent_at,
            m.read_at
        FROM inbox_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.recipient_id = ?
        ORDER BY m.sent_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit,   PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Paginated list of inbox_messages for the full-page inbox.
 *
 * Scoped to recipient_id = $user_id.
 * Supports $filter: 'all' | 'unread' | 'today' | 'week'
 * Supports $q: case-insensitive LIKE search on subject, body, and sender full_name.
 *
 * Returns rows with at minimum: message_id, sender_id, sender_name, subject,
 * body, sent_at, read_at.
 *
 * @param PDO    $pdo
 * @param int    $user_id  Authenticated user (recipient).
 * @param string $q        Search keyword (empty = no search filter).
 * @param string $filter   'all' | 'unread' | 'today' | 'week'
 * @param int    $page     1-based page number.
 * @param int    $per      Rows per page.
 * @return array
 */
function get_inbox_messages_page(PDO $pdo, int $user_id, string $q = '', string $filter = 'all', int $page = 1, int $per = 20): array {
    $where  = ['m.recipient_id = ?'];
    $params = [$user_id];

    if ($filter === 'unread') {
        $where[] = 'm.read_at IS NULL';
    } elseif ($filter === 'today') {
        $where[] = 'DATE(m.sent_at) = CURDATE()';
    } elseif ($filter === 'week') {
        $where[] = 'm.sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    }

    if ($q !== '') {
        $kw      = '%' . $q . '%';
        $where[] = '(m.subject LIKE ? OR m.body LIKE ? OR u.full_name LIKE ?)';
        array_push($params, $kw, $kw, $kw);
    }

    $where_str = implode(' AND ', $where);
    $offset    = max(0, ($page - 1) * $per);

    $stmt = $pdo->prepare("
        SELECT m.message_id, m.sender_id, u.full_name AS sender_name,
               m.recipient_id, m.wo_id, m.ticket_id,
               m.subject, m.body, m.sent_at, m.read_at
        FROM inbox_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE $where_str
        ORDER BY m.sent_at DESC
        LIMIT $per OFFSET $offset
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Count of inbox_messages matching the same WHERE conditions as
 * get_inbox_messages_page(), used for pagination.
 *
 * @param PDO    $pdo
 * @param int    $user_id  Authenticated user (recipient).
 * @param string $q        Search keyword (empty = no search filter).
 * @param string $filter   'all' | 'unread' | 'today' | 'week'
 * @return int
 */
function count_inbox_messages_page(PDO $pdo, int $user_id, string $q = '', string $filter = 'all'): int {
    $where  = ['m.recipient_id = ?'];
    $params = [$user_id];

    if ($filter === 'unread') {
        $where[] = 'm.read_at IS NULL';
    } elseif ($filter === 'today') {
        $where[] = 'DATE(m.sent_at) = CURDATE()';
    } elseif ($filter === 'week') {
        $where[] = 'm.sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    }

    if ($q !== '') {
        $kw      = '%' . $q . '%';
        $where[] = '(m.subject LIKE ? OR m.body LIKE ? OR u.full_name LIKE ?)';
        array_push($params, $kw, $kw, $kw);
    }

    $where_str = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM inbox_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE $where_str
    ");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Fetch a single inbox_message by ID, scoped to the given recipient.
 * Returns the message row (with sender_name from users JOIN) or false
 * if the message does not exist or is not owned by $user_id.
 *
 * Requirements: 1.5, 8.1
 */
function get_inbox_message(PDO $pdo, int $message_id, int $user_id): array|false {
    $stmt = $pdo->prepare("
        SELECT m.message_id, m.sender_id, m.recipient_id,
               m.wo_id, m.ticket_id,
               m.subject, m.body,
               m.sent_at, m.read_at,
               u.full_name AS sender_name
        FROM inbox_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.message_id = ?
          AND m.recipient_id = ?
    ");
    $stmt->execute([$message_id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : false;
}

// ─── Unified Inbox helpers (Requirements 1.4, 5.8) ───────────────────────────

/**
 * Return a paginated unified inbox for IT-role users.
 *
 * Merges email-channel tickets (row_type = 'email') with inbox_messages
 * addressed to $user_id (row_type = 'message') into a single result set,
 * ordered by timestamp descending.
 *
 * Columns returned per row:
 *   id, row_type, sender_name, subject, preview, ts, read_at,
 *   ticket_id, message_id
 *
 * @param PDO    $pdo
 * @param int    $user_id  Authenticated user's ID (used to scope inbox_messages)
 * @param string $q        Search keyword (empty = no search filter)
 * @param string $filter   'all' | 'unread' | 'today' | 'week'
 * @param int    $page     1-based page number
 * @param int    $per      Rows per page
 *
 * Requirements: 1.4, 5.8
 */
function get_unified_inbox_page(
    PDO $pdo,
    int $user_id,
    string $q,
    string $filter,
    int $page,
    int $per
): array {
    [$email_sql, $params_email, $msg_sql, $params_message] =
        _unified_inbox_sql_parts($user_id, $q, $filter);

    $offset    = max(0, ($page - 1) * $per);
    $union_sql = "
        ($email_sql)
        UNION ALL
        ($msg_sql)
        ORDER BY ts DESC
        LIMIT $per OFFSET $offset
    ";

    $stmt = $pdo->prepare($union_sql);
    $stmt->execute(array_merge($params_email, $params_message));
    return $stmt->fetchAll();
}

/**
 * Return the total row count for the unified inbox (used for pagination).
 *
 * Wraps the same UNION as get_unified_inbox_page() in a COUNT(*) subquery.
 *
 * Requirements: 1.4, 5.8
 */
function count_unified_inbox_page(
    PDO $pdo,
    int $user_id,
    string $q,
    string $filter
): int {
    [$email_sql, $params_email, $msg_sql, $params_message] =
        _unified_inbox_sql_parts($user_id, $q, $filter);

    $count_sql = "
        SELECT COUNT(*) FROM (
            ($email_sql)
            UNION ALL
            ($msg_sql)
        ) AS unified
    ";

    $stmt = $pdo->prepare($count_sql);
    $stmt->execute(array_merge($params_email, $params_message));
    return (int) $stmt->fetchColumn();
}

/**
 * Internal helper — builds the two UNION halves with explicit COLLATE so
 * MySQL never throws "Illegal mix of collations" regardless of the server's
 * default charset/collation settings.
 *
 * Returns [$email_sql, $params_email, $msg_sql, $params_message].
 */
function _unified_inbox_sql_parts(
    int $user_id,
    string $q,
    string $filter
): array {
    $params_email   = [];
    $params_message = [];

    // ── Email half ────────────────────────────────────────────────────────────
    $email_where = ["t.channel = 'email'"];

    if ($filter === 'unread') {
        $email_where[] = "t.email_seen_at IS NULL";
    } elseif ($filter === 'today') {
        $email_where[] = "DATE(t.created_at) = CURDATE()";
    } elseif ($filter === 'week') {
        $email_where[] = "t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    }

    if ($q !== '') {
        $kw = '%' . $q . '%';
        $email_where[] = "(t.title LIKE ? OR t.description LIKE ?
                           OR t.external_name_from LIKE ? OR u.full_name LIKE ?)";
        array_push($params_email, $kw, $kw, $kw, $kw);
    }

    $email_where_str = implode(' AND ', $email_where);

    // Every string column is explicitly cast to utf8mb4_unicode_ci so the
    // UNION never hits a collation mismatch with inbox_messages columns.
    $email_sql = "
        SELECT
            t.ticket_id                                                                                AS id,
            CONVERT('email'                                    USING utf8mb4) COLLATE utf8mb4_unicode_ci AS row_type,
            CONVERT(COALESCE(u.full_name, t.external_name_from) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS sender_name,
            CONVERT(t.title                                    USING utf8mb4) COLLATE utf8mb4_unicode_ci AS subject,
            CONVERT(LEFT(COALESCE(t.description,''), 120)      USING utf8mb4) COLLATE utf8mb4_unicode_ci AS preview,
            t.created_at                                                                               AS ts,
            t.email_seen_at                                                                            AS read_at,
            t.ticket_id                                                                                AS ticket_id,
            NULL                                                                                       AS message_id
        FROM tickets t
        LEFT JOIN users u ON t.requester_id = u.user_id
        WHERE $email_where_str
    ";

    // ── Message half ──────────────────────────────────────────────────────────
    $msg_where      = ["m.recipient_id = ?"];
    $params_message[] = $user_id;

    if ($filter === 'unread') {
        $msg_where[] = "m.read_at IS NULL";
    } elseif ($filter === 'today') {
        $msg_where[] = "DATE(m.sent_at) = CURDATE()";
    } elseif ($filter === 'week') {
        $msg_where[] = "m.sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    }

    if ($q !== '') {
        $kw = '%' . $q . '%';
        $msg_where[] = "(m.subject LIKE ? OR m.body LIKE ? OR u.full_name LIKE ?)";
        array_push($params_message, $kw, $kw, $kw);
    }

    $msg_where_str = implode(' AND ', $msg_where);

    $msg_sql = "
        SELECT
            m.message_id                                                                               AS id,
            CONVERT('message'          USING utf8mb4) COLLATE utf8mb4_unicode_ci                      AS row_type,
            CONVERT(u.full_name        USING utf8mb4) COLLATE utf8mb4_unicode_ci                      AS sender_name,
            CONVERT(m.subject          USING utf8mb4) COLLATE utf8mb4_unicode_ci                      AS subject,
            CONVERT(LEFT(m.body, 120)  USING utf8mb4) COLLATE utf8mb4_unicode_ci                      AS preview,
            m.sent_at                                                                                  AS ts,
            m.read_at                                                                                  AS read_at,
            NULL                                                                                       AS ticket_id,
            m.message_id                                                                               AS message_id
        FROM inbox_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE $msg_where_str
    ";

    return [$email_sql, $params_email, $msg_sql, $params_message];
}
