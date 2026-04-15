<?php
// modules/tickets/functions.php
// Database queries and helpers for Request Submission & Intake

// ── Stats ─────────────────────────────────────────────────────

function get_ticket_stats(PDO $pdo): array {
    $row = $pdo->query("
        SELECT COUNT(*)                        AS total,
               SUM(status = 'new')            AS t_new,
               SUM(status = 'assigned')       AS assigned,
               SUM(status = 'in_progress')    AS in_progress,
               SUM(status = 'on_hold')        AS on_hold,
               SUM(status = 'resolved')       AS resolved,
               SUM(status = 'closed')         AS closed
        FROM tickets
    ")->fetch();

    return $row;
}

// ── Listing ───────────────────────────────────────────────────

function get_tickets(PDO $pdo, array $f = [], int $page = 1, int $per = 10): array {
    [$where, $params] = _ticket_where($f);
    $offset = ($page - 1) * $per;

    $sort_map = [
        'ticket_number' => 't.ticket_number',
        'priority'      => "FIELD(t.priority, 'critical', 'high', 'medium', 'low')",
        'status'        => 't.status',
        'created_at'    => 't.created_at',
        'updated_at'    => 't.updated_at',
    ];
    $sort_col = $sort_map[$f['sort_col'] ?? ''] ?? 't.updated_at';
    $sort_dir = strtoupper($f['sort_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $pdo->prepare("
        SELECT t.ticket_id, t.ticket_number, t.title, t.status, t.priority, t.created_at, t.updated_at,
               t.is_event_support, t.assigned_team, t.on_hold_reason,
               r.full_name AS requester_name,
               a.asset_tag, a.asset_id,
               c.category_name,
               u.full_name AS assigned_to_name
        FROM tickets t
        LEFT JOIN users r            ON t.requester_id = r.user_id
        LEFT JOIN assets a           ON t.asset_id = a.asset_id
        LEFT JOIN asset_categories c ON t.category_id = c.category_id
        LEFT JOIN users u            ON t.assigned_to = u.user_id
        WHERE $where
        ORDER BY $sort_col $sort_dir
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per, $offset]));
    return $stmt->fetchAll();
}

function count_tickets(PDO $pdo, array $f = []): int {
    [$where, $params] = _ticket_where($f);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets t
        LEFT JOIN users r  ON t.requester_id = r.user_id
        LEFT JOIN assets a ON t.asset_id     = a.asset_id
        WHERE $where
    ");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function _ticket_where(array $f): array {
    $where  = ['1=1'];
    $params = [];

    // Base scoping (if restricted by role)
    if (!empty($f['requester_id'])) {
        $where[]  = 't.requester_id = ?';
        $params[] = (int) $f['requester_id'];
    }

    if (!empty($f['q'])) {
        $q = '%' . $f['q'] . '%';
        $where[] = '(t.ticket_number LIKE ? OR t.title LIKE ? OR r.full_name LIKE ? OR a.asset_tag LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }
    if (!empty($f['status'])) {
        $where[]  = 't.status = ?';
        $params[] = $f['status'];
    }
    if (!empty($f['priority'])) {
        $where[]  = 't.priority = ?';
        $params[] = $f['priority'];
    }
    
    return [implode(' AND ', $where), $params];
}

// ── Single Ticket ─────────────────────────────────────────────

function get_ticket_by_id(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare("
        SELECT t.*,
               r.full_name AS requester_name, r.email AS requester_email, r.contact_number,
               d.department_name AS requester_dept,
               a.asset_tag, a.manufacturer, a.model,
               c.category_name,
               l.building, l.floor, l.room,
               u_assign.full_name AS assigned_to_name,
               u_app.full_name AS approved_by_name
        FROM tickets t
        LEFT JOIN users r            ON t.requester_id = r.user_id
        LEFT JOIN departments d      ON r.department_id = d.department_id
        LEFT JOIN assets a           ON t.asset_id = a.asset_id
        LEFT JOIN asset_categories c ON t.category_id = c.category_id
        LEFT JOIN locations l        ON t.location_id = l.location_id
        LEFT JOIN users u_assign     ON t.assigned_to = u_assign.user_id
        LEFT JOIN users u_app        ON t.approved_by = u_app.user_id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ── Attachments, Comments & Dynamic Fields ────────────────────

function get_ticket_attachments(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT ta.*, u.full_name AS uploaded_by_name
        FROM ticket_attachments ta
        LEFT JOIN users u ON ta.uploaded_by = u.user_id
        WHERE ta.ticket_id = ?
        ORDER BY ta.uploaded_at DESC
    ");
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_ticket_comments(PDO $pdo, int $id, bool $include_internal = true): array {
    $sql = "
        SELECT tc.*, u.full_name AS user_name, u.profile_picture
        FROM ticket_comments tc
        LEFT JOIN users u ON tc.user_id = u.user_id
        WHERE tc.ticket_id = ?
    ";
    if (!$include_internal) {
        $sql .= " AND tc.is_internal = 0 ";
    }
    $sql .= " ORDER BY tc.created_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_ticket_dynamic_fields(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT field_name, field_value FROM ticket_dynamic_fields WHERE ticket_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function get_all_categories(PDO $pdo): array {
    return $pdo->query("SELECT * FROM asset_categories ORDER BY category_name")->fetchAll();
}

function get_all_locations(PDO $pdo): array {
    return $pdo->query("SELECT * FROM locations ORDER BY building, floor, room")->fetchAll();
}

// ── Write Operations ──────────────────────────────────────────

function generate_ticket_number(PDO $pdo): string {
    $year = date('Y');
    $last = $pdo->query("
        SELECT ticket_number FROM tickets
        WHERE ticket_number LIKE 'TKT-$year-%'
        ORDER BY ticket_id DESC LIMIT 1
    ")->fetchColumn();

    if ($last) {
        $seq = (int) substr($last, strrpos($last, '-') + 1) + 1;
    } else {
        $seq = 1;
    }
    return 'TKT-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

function calculate_priority(string $urgency, string $impact): string {
    // A simple matrix for Priority = Urgency x Impact
    $matrix = [
        'critical' => ['critical'=>'critical', 'high'=>'critical', 'medium'=>'high', 'low'=>'high'],
        'high'     => ['critical'=>'critical', 'high'=>'high',     'medium'=>'high', 'low'=>'medium'],
        'medium'   => ['critical'=>'high',     'high'=>'high',     'medium'=>'medium','low'=>'low'],
        'low'      => ['critical'=>'high',     'high'=>'medium',   'medium'=>'low',   'low'=>'low'],
    ];
    return $matrix[$urgency][$impact] ?? 'medium';
}

function create_ticket(PDO $pdo, array $d): int {
    $ticket_number = generate_ticket_number($pdo);
    $priority = calculate_priority($d['urgency'], $d['impact']);

    $pdo->prepare("
        INSERT INTO tickets
            (ticket_number, requester_id, asset_id, category_id, location_id,
             title, description, impact, urgency, priority, channel,
             is_event_support, preferred_window, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $ticket_number,
        $d['requester_id'],
        $d['asset_id'] ?: null,
        $d['category_id'] ?: null,
        $d['location_id'] ?: null,
        $d['title'],
        $d['description'] ?: null,
        $d['impact'] ?? 'medium',
        $d['urgency'] ?? 'medium',
        $priority,
        $d['channel'] ?? 'web',
        $d['is_event_support'] ?? 0,
        $d['preferred_window'] ?: null,
        'new',
    ]);

    $ticket_id = (int) $pdo->lastInsertId();
    
    // Save dynamic fields if any
    if (!empty($d['dynamic_fields']) && is_array($d['dynamic_fields'])) {
        $stmt = $pdo->prepare("INSERT INTO ticket_dynamic_fields (ticket_id, field_name, field_value) VALUES (?, ?, ?)");
        foreach ($d['dynamic_fields'] as $key => $val) {
            if (trim($val) !== '') {
                $stmt->execute([$ticket_id, $key, $val]);
            }
        }
    }

    return $ticket_id;
}

function update_ticket(PDO $pdo, int $id, array $d): void {
    // For IT staff updating status
    if (isset($d['status'])) {
        $pdo->prepare("
            UPDATE tickets SET
                title=?, description=?, status=?, on_hold_reason=?,
                impact=?, urgency=?, priority=?, is_event_support=?,
                assigned_to=?, category_id=?, location_id=?
            WHERE ticket_id=?
        ")->execute([
            $d['title'],
            $d['description'],
            $d['status'],
            $d['on_hold_reason'] ?: null,
            $d['impact'],
            $d['urgency'],
            $d['priority'],
            $d['is_event_support'] ?? 0,
            $d['assigned_to'] ?: null,
            $d['category_id'] ?: null,
            $d['location_id'] ?: null,
            $id
        ]);
        
        if ($d['status'] === 'resolved' || $d['status'] === 'closed') {
            $pdo->prepare("UPDATE tickets SET ".($d['status'] === 'resolved' ? "resolved_at" : "closed_at")." = NOW() WHERE ticket_id=?")->execute([$id]);
        }
        
    } else {
        // Just the basic user update
        $priority = calculate_priority($d['urgency'], $d['impact']);
        $pdo->prepare("
            UPDATE tickets SET
                title=?, description=?, impact=?, urgency=?, priority=?,
                is_event_support=?, category_id=?, location_id=?, preferred_window=?
            WHERE ticket_id=?
        ")->execute([
            $d['title'],
            $d['description'],
            $d['impact'],
            $d['urgency'],
            $priority,
            $d['is_event_support'] ?? 0,
            $d['category_id'] ?: null,
            $d['location_id'] ?: null,
            $d['preferred_window'] ?: null,
            $id
        ]);
    }
    
    // Update or inserting dynamic fields
    if (isset($d['dynamic_fields']) && is_array($d['dynamic_fields'])) {
        $pdo->prepare("DELETE FROM ticket_dynamic_fields WHERE ticket_id = ?")->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO ticket_dynamic_fields (ticket_id, field_name, field_value) VALUES (?, ?, ?)");
        foreach ($d['dynamic_fields'] as $key => $val) {
            if (trim($val) !== '') {
                $stmt->execute([$id, $key, $val]);
            }
        }
    }
}

function check_duplicate_ticket(PDO $pdo, int $asset_id, string $description, int $days = 7): ?int {
    if (!$asset_id) return null;
    $stmt = $pdo->prepare("
        SELECT ticket_id 
        FROM tickets 
        WHERE asset_id = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
          AND status NOT IN ('closed', 'cancelled')
          AND ticket_id != ? -- if updating
          AND description LIKE ?
        LIMIT 1
    ");
    $stmt->execute([$asset_id, $days, 0, substr($description, 0, 50) . '%']);
    return $stmt->fetchColumn() ?: null;
}

function handle_ticket_uploads(PDO $pdo, int $ticket_id, int $user_id): void {
    if (!empty($_FILES['attachments']['name'][0])) {
        $upload_dir = __DIR__ . '/../../public/uploads/tickets/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $stmt_att = $pdo->prepare("INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_type, file_size_kb, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
            $tmp_name = $_FILES['attachments']['tmp_name'][$i];
            $name     = basename($_FILES['attachments']['name'][$i]);
            $size     = $_FILES['attachments']['size'][$i];
            $error    = $_FILES['attachments']['error'][$i];

            if ($error === UPLOAD_ERR_OK && $size > 0) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $safe_name = $ticket_id . '_' . time() . '_' . rand(100,999) . '.' . $ext;
                $dest = $upload_dir . $safe_name;
                
                if (move_uploaded_file($tmp_name, $dest)) {
                    $stmt_att->execute([
                        $ticket_id, 
                        $name, 
                        'public/uploads/tickets/' . $safe_name, 
                        $ext, 
                        round($size / 1024), 
                        $user_id
                    ]);
                }
            }
        }
    }
}

// ── Render Helpers ────────────────────────────────────────────

function ticket_status_badge(string $status): string {
    $labels = [
        'new'         => 'New',
        'assigned'    => 'Assigned',
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In Progress',
        'on_hold'     => 'On Hold',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
        'cancelled'   => 'Cancelled',
    ];
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    return '<span class="wo-badge badge-' . htmlspecialchars($status) . '"><span class="bdot"></span>' . $label . '</span>';
}

function ticket_priority_badge(?string $priority): string {
    if (!$priority) return '<span class="text-gray-300">—</span>';
    $label = ucfirst($priority);
    return '<span class="wo-badge badge-priority badge-' . htmlspecialchars($priority) . '">' . $label . '</span>';
}

function ticket_time_ago(string $datetime): string {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->days === 0 && $diff->h === 0 && $diff->i === 0) return 'Just now';
    if ($diff->days === 0 && $diff->h === 0) return $diff->i . ' min ago';
    if ($diff->days === 0) return $diff->h . ' hours ago';
    if ($diff->days === 1) return 'Yesterday';
    if ($diff->days < 7)   return $diff->days . ' days ago';
    if ($diff->days < 14)  return '1 week ago';
    if ($diff->days < 30)  return intdiv($diff->days, 7) . ' weeks ago';
    if ($diff->days < 60)  return '1 month ago';
    return intdiv($diff->days, 30) . ' months ago';
}
