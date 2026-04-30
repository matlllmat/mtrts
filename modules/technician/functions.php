<?php
// modules/technician/functions.php
// All database queries and helpers for the Technician Operations module.
// $pdo is provided by the hub; never create a new connection here.

// ── Work Order Listing ───────────────────────────────────────

function tech_dbg(string $hypothesisId, string $location, string $message, array $data = [], string $runId = 'pre-fix'): void {
    try {
        $row = [
            'sessionId' => '30aee9',
            'runId' => $runId,
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int)floor(microtime(true) * 1000),
        ];
        @file_put_contents(__DIR__ . '/../../debug-30aee9.log', json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    } catch (Throwable $e) {}
}

function tech_table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function technician_has_role_queue_schema(PDO $pdo): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'work_orders'
              AND COLUMN_NAME = 'assigned_role_id'
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function tech_is_admin_role(PDO $pdo): bool {
    $role = null;
    if (function_exists('current_role_name')) {
        try { $role = current_role_name($pdo); } catch (Throwable $e) { $role = null; }
    }
    return in_array((string)$role, ['admin', 'super_admin'], true);
}

function get_all_queue_work_orders(PDO $pdo): array {
    // Everyone can view all work orders (queue-without-claim list).
    // Use ticket.priority (NOT work_orders.priority; that column does not exist in schema).
    try {
        if (!technician_has_role_queue_schema($pdo)) {
            $stmt = $pdo->prepare("
                SELECT wo.wo_id, wo.wo_number, wo.status,
                       NULL AS assigned_role_id,
                       wo.assigned_to,
                       COALESCE(t.priority, 'medium') AS priority,
                       t.title AS ticket_title, t.description AS ticket_description,
                       wo.notes, wo.wo_type,
                       l.building, l.floor, l.room,
                       u.full_name AS requester_name, u.contact_number, u.email,
                       assigned_user.full_name AS assigned_to_name,
                       wo.created_at, wo.scheduled_end
                FROM work_orders wo
                LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
                LEFT JOIN locations l ON t.location_id = l.location_id
                LEFT JOIN users u ON t.requester_id = u.user_id
                LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
                ORDER BY wo.created_at DESC
                LIMIT 250
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $pdo->prepare("
            SELECT wo.wo_id, wo.wo_number, wo.status,
                   wo.assigned_role_id,
                   wo.assigned_to,
                   COALESCE(t.priority, 'medium') AS priority,
                   t.title AS ticket_title, t.description AS ticket_description,
                   wo.notes, wo.wo_type,
                   l.building, l.floor, l.room,
                   u.full_name AS requester_name, u.contact_number, u.email,
                   assigned_user.full_name AS assigned_to_name,
                   wo.created_at, wo.scheduled_end
            FROM work_orders wo
            LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
            LEFT JOIN locations l ON t.location_id = l.location_id
            LEFT JOIN users u ON t.requester_id = u.user_id
            LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
            ORDER BY wo.created_at DESC
            LIMIT 250
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_SQL', 'modules/technician/functions.php:get_all_queue_work_orders', 'List query failed', [
            'msg' => $e->getMessage(),
        ]);
        return [];
    }
}

function get_assigned_work_orders(PDO $pdo, int $technician_id): array {
    $stmt = $pdo->prepare("
        SELECT wo.wo_id, wo.wo_number, wo.status,
               COALESCE(t.priority, 'medium') AS priority,
               t.title AS ticket_title, t.description AS ticket_description,
               wo.notes, wo.wo_type,
               l.building, l.floor, l.room,
               u.full_name AS requester_name, u.contact_number, u.email,
               assigned_user.full_name AS assigned_to_name,
               wo.assigned_to,
               wo.created_at, wo.scheduled_end
        FROM work_orders wo
        LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
        LEFT JOIN locations l ON t.location_id = l.location_id
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
        WHERE wo.assigned_to = ? AND wo.status IN ('assigned', 'in_progress', 'scheduled', 'resolved', 'closed')
        ORDER BY FIELD(COALESCE(t.priority, 'medium'), 'critical', 'high', 'medium', 'low'), wo.created_at DESC
    ");
    $stmt->execute([$technician_id]);
    return $stmt->fetchAll();
}

function get_work_order_stats(PDO $pdo, int $technician_id): array {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'assigned') AS pending,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'resolved') AS completed
        FROM work_orders
        WHERE assigned_to = ? AND status IN ('assigned', 'scheduled', 'in_progress', 'resolved', 'closed')
    ");
    $stmt->execute([$technician_id]);
    return $stmt->fetch();
}

// ── Work Order Detail ───────────────────────────────────────

function get_work_order_detail(PDO $pdo, int $wo_id): ?array {
    $stmt = $pdo->prepare("
        SELECT wo.*,
               COALESCE(t.priority, 'medium') AS priority,
               t.title AS ticket_title, t.description AS ticket_description,
               l.building, l.floor, l.room,
               u.full_name AS requester_name, u.contact_number, u.email,
               ac.category_name,
               a.asset_tag, a.serial_number, a.manufacturer, a.model
        FROM work_orders wo
        LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
        LEFT JOIN locations l ON t.location_id = l.location_id
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN assets a ON t.asset_id = a.asset_id
        LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
        WHERE wo.wo_id = ?
    ");
    $stmt->execute([$wo_id]);
    $row = $stmt->fetch() ?: null;
    // #region agent log
    tech_dbg('H_PRIORITY', 'modules/technician/functions.php:get_work_order_detail', 'Fetched detail row', [
        'wo_id' => $wo_id,
        'has_row' => (bool)$row,
        'has_priority_key' => is_array($row) && array_key_exists('priority', $row),
        'priority' => is_array($row) ? ($row['priority'] ?? null) : null,
    ]);
    // #endregion
    return $row;
}

// ── Checklist ───────────────────────────────────────────────

function get_checklist_for_work_order(PDO $pdo, int $wo_id): array {
    try {
        // Get asset category from ticket
        $stmt = $pdo->prepare("
            SELECT ac.category_id
            FROM work_orders wo
            JOIN tickets t ON wo.ticket_id = t.ticket_id
            LEFT JOIN assets a ON t.asset_id = a.asset_id
            LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
            WHERE wo.wo_id = ?
        ");
        $stmt->execute([$wo_id]);
        $category_id = $stmt->fetchColumn();

        tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:get_checklist_for_work_order', 'Category lookup', [
            'wo_id' => $wo_id,
            'category_id' => $category_id,
        ]);

        // Get checklist for category, fallback to general checklist
        $checklist_id = null;
        
        // First try to get category-specific checklist
        if ($category_id) {
            $stmt = $pdo->prepare("
                SELECT checklist_id FROM wo_checklists
                WHERE category_id = ?
                LIMIT 1
            ");
            $stmt->execute([$category_id]);
            $checklist_id = $stmt->fetchColumn();
            
            tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:get_checklist_for_work_order', 'Found category checklist', [
                'category_id' => $category_id,
                'checklist_id' => $checklist_id,
            ]);
        }
        
        // If no category-specific checklist, get the General Repair Checklist (NULL category)
        if (!$checklist_id) {
            $stmt = $pdo->prepare("
                SELECT checklist_id FROM wo_checklists
                WHERE category_id IS NULL
                LIMIT 1
            ");
            $stmt->execute();
            $checklist_id = $stmt->fetchColumn();
            
            tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:get_checklist_for_work_order', 'Using general checklist', [
                'checklist_id' => $checklist_id,
            ]);
        }

        // If still no checklist found, return empty array
        if (!$checklist_id) {
            tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:get_checklist_for_work_order', 'NO CHECKLIST FOUND', [
                'wo_id' => $wo_id,
            ]);
            return [];
        }

        // Get items with verification fields
        $stmt = $pdo->prepare("
            SELECT i.item_id, i.item_text, i.is_mandatory, i.requires_photo,
                   COALESCE(i.is_verifiable, 0) AS is_verifiable, 
                   i.verification_type,
                   COALESCE(c.is_done, 0) AS is_done, 
                   c.notes, 
                   c.completed_at,
                   c.completed_by
            FROM wo_checklist_items i
            LEFT JOIN wo_checklist_completions c ON i.item_id = c.item_id AND c.wo_id = ?
            WHERE i.checklist_id = ?
            ORDER BY i.sort_order
        ");
        $stmt->execute([$wo_id, $checklist_id]);
        $items = $stmt->fetchAll();
        
        tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:get_checklist_for_work_order', 'Checklist loaded', [
            'wo_id' => $wo_id,
            'checklist_id' => $checklist_id,
            'item_count' => count($items),
        ]);
        
        return $items;
    } catch (Throwable $e) {
        tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:get_checklist_for_work_order', 'Failed to fetch checklist', [
            'wo_id' => $wo_id,
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}

// ── Safety Checks ───────────────────────────────────────────

function get_safety_checks_for_work_order(PDO $pdo, int $wo_id): array {
    try {
        $stmt = $pdo->prepare("
            SELECT s.safety_id, s.check_text AS safety_text, s.is_mandatory,
                   COALESCE(c.is_done, 0) AS is_done, 
                   c.notes, 
                   c.completed_at,
                   c.completed_by
            FROM wo_safety_checks s
            LEFT JOIN wo_safety_completions c ON s.safety_id = c.safety_id AND c.wo_id = ?
            ORDER BY s.sort_order
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_SAFETY', 'modules/technician/functions.php:get_safety_checks_for_work_order', 'Failed to fetch safety checks', [
            'wo_id' => $wo_id,
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}

function update_safety_completion(PDO $pdo, int $wo_id, int $safety_id, bool $is_done, ?string $notes = null): bool {
    try {
        $technician_id = $_SESSION['user_id'] ?? null;
        
        if ($is_done) {
            $stmt = $pdo->prepare("
                INSERT INTO wo_safety_completions (wo_id, safety_id, is_done, notes, completed_by, completed_at)
                VALUES (?, ?, 1, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_done = 1, notes = VALUES(notes), completed_by = VALUES(completed_by), completed_at = NOW()
            ");
            return $stmt->execute([$wo_id, $safety_id, $notes, $technician_id]);
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM wo_safety_completions WHERE wo_id = ? AND safety_id = ?
            ");
            return $stmt->execute([$wo_id, $safety_id]);
        }
    } catch (Throwable $e) {
        tech_dbg('H_SAFETY', 'modules/technician/functions.php:update_safety_completion', 'Failed to update safety completion', [
            'wo_id' => $wo_id,
            'safety_id' => $safety_id,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

// ── Time Logs ───────────────────────────────────────────────

function get_time_logs(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT * FROM wo_time_logs
        WHERE wo_id = ?
        ORDER BY logged_at ASC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}

function calculate_total_time(array $logs): int {
    $total = 0;
    $start = null;
    foreach ($logs as $log) {
        if ($log['action'] === 'start' || $log['action'] === 'resume') {
            $start = strtotime($log['logged_at']);
        } elseif (($log['action'] === 'pause' || $log['action'] === 'stop') && $start) {
            $total += strtotime($log['logged_at']) - $start;
            $start = null;
        }
    }
    return $total;
}

// ── Notes ───────────────────────────────────────────────────

function get_work_order_notes(PDO $pdo, int $wo_id): array {
    if (!tech_table_exists($pdo, 'wo_notes')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_notes', 'Missing table wo_notes', ['wo_id' => $wo_id]);
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, u.full_name AS added_by_name
            FROM wo_notes n
            LEFT JOIN users u ON n.added_by = u.user_id
            WHERE n.wo_id = ?
            ORDER BY n.added_at ASC
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_notes', 'wo_notes query failed', ['wo_id' => $wo_id, 'error' => $e->getMessage()]);
        return [];
    }
}

// ── Media ───────────────────────────────────────────────────

function get_work_order_media(PDO $pdo, int $wo_id): array {
    if (!tech_table_exists($pdo, 'wo_media')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_media', 'Missing table wo_media', ['wo_id' => $wo_id]);
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM wo_media
            WHERE wo_id = ?
            ORDER BY uploaded_at ASC
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_media', 'wo_media query failed', ['wo_id' => $wo_id]);
        return [];
    }
}

// ── Parts ───────────────────────────────────────────────────

function get_work_order_parts(PDO $pdo, int $wo_id): array {
    if (!tech_table_exists($pdo, 'wo_parts_used')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_parts', 'Missing table wo_parts_used', ['wo_id' => $wo_id]);
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pi.part_name, pi.part_number
            FROM wo_parts_used p
            JOIN parts_inventory pi ON p.part_id = pi.part_id
            WHERE p.wo_id = ?
            ORDER BY p.used_at ASC
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_parts', 'wo_parts_used query failed', ['wo_id' => $wo_id]);
        return [];
    }
}

// ── Sign-off ───────────────────────────────���────────────────

function get_work_order_signoff(PDO $pdo, int $wo_id): ?array {
    if (!tech_table_exists($pdo, 'wo_signoff')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_signoff', 'Missing table wo_signoff', ['wo_id' => $wo_id]);
        return null;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM wo_signoff WHERE wo_id = ?");
        $stmt->execute([$wo_id]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_signoff', 'wo_signoff query failed', ['wo_id' => $wo_id]);
        return null;
    }
}

// ── Save Functions ──────────────────────────────────────────

function save_time_log(PDO $pdo, int $wo_id, int $technician_id, string $action, ?string $labor_type = null, ?string $notes = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $technician_id, $action, $labor_type, $notes]);
}

function complete_work_order_transactional(PDO $pdo, array $payload, int $technician_id): array {
    $wo_id               = (int)($payload['wo_id'] ?? 0);
    $checklist_map       = is_array($payload['checklist'] ?? null) ? $payload['checklist'] : [];
    $safety_map          = is_array($payload['safety'] ?? null) ? $payload['safety'] : [];
    $time_logs           = is_array($payload['time_logs'] ?? null) ? $payload['time_logs'] : [];
    $signer_name         = trim((string)($payload['signer_name'] ?? ''));
    $signer_satisfaction = (int)($payload['signer_satisfaction'] ?? 0);
    $feedback            = trim((string)($payload['feedback'] ?? ''));
    $signature_data_url  = trim((string)($payload['signature_data_url'] ?? ''));
    $resolution_notes    = trim((string)($payload['resolution_notes'] ?? ''));

    if ($wo_id <= 0 || $technician_id <= 0 || $signer_name === '') {
        throw new InvalidArgumentException('Missing required fields: wo_id, signer_name');
    }

    $stmt = $pdo->prepare("SELECT wo_id FROM work_orders WHERE wo_id = ?");
    $stmt->execute([$wo_id]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('Work order not found');
    }

    // Signature path is required in some schemas, so always provide a value.
    $signature_path = 'data:inline';
    if ($signature_data_url && str_starts_with($signature_data_url, 'data:image/')) {
        $upload_dir = __DIR__ . '/../uploads/signatures/' . $wo_id . '/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        $img_data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $signature_data_url));
        $filename = 'signoff_' . time() . '.png';
        file_put_contents($upload_dir . $filename, $img_data);
        $signature_path = BASE_URL . 'modules/technician/uploads/signatures/' . $wo_id . '/' . $filename;
    }

    $pdo->beginTransaction();
    try {
        foreach ($checklist_map as $item_id => $done) {
            $item_id = (int)$item_id;
            if ($item_id <= 0) continue;
            if ((bool)$done) {
                $stmt = $pdo->prepare("
                    INSERT INTO wo_checklist_completions (wo_id, item_id, is_done, completed_by, completed_at)
                    VALUES (?, ?, 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        is_done = 1,
                        completed_by = VALUES(completed_by),
                        completed_at = NOW()
                ");
                $stmt->execute([$wo_id, $item_id, $technician_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM wo_checklist_completions WHERE wo_id = ? AND item_id = ?");
                $stmt->execute([$wo_id, $item_id]);
            }
        }

        foreach ($safety_map as $safety_id => $done) {
            $safety_id = (int)$safety_id;
            if ($safety_id <= 0) continue;
            if ((bool)$done) {
                $stmt = $pdo->prepare("
                    INSERT INTO wo_safety_completions (wo_id, safety_id, is_done, completed_by, completed_at)
                    VALUES (?, ?, 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        is_done = 1,
                        completed_by = VALUES(completed_by),
                        completed_at = NOW()
                ");
                $stmt->execute([$wo_id, $safety_id, $technician_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM wo_safety_completions WHERE wo_id = ? AND safety_id = ?");
                $stmt->execute([$wo_id, $safety_id]);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO wo_signoff
                (wo_id, signer_name, signature_path, satisfaction, feedback, signed_by_user_id, signed_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                signer_name       = VALUES(signer_name),
                signature_path    = VALUES(signature_path),
                satisfaction      = VALUES(satisfaction),
                feedback          = VALUES(feedback),
                signed_by_user_id = VALUES(signed_by_user_id),
                signed_at         = NOW()
        ");
        $stmt->execute([
            $wo_id,
            $signer_name,
            $signature_path,
            $signer_satisfaction ?: null,
            $feedback ?: null,
            $technician_id,
        ]);

        if (!empty($time_logs)) {
            foreach ($time_logs as $log) {
                $elapsed_ms = (int)($log['elapsed_ms'] ?? 0);
                $labor_type = trim((string)($log['labor_type'] ?? ''));
                $notes = 'Completed segment';
                if ($elapsed_ms > 0) $notes .= ' (' . $elapsed_ms . 'ms)';
                try {
                    $pdo->prepare("
                        INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, elapsed_ms, notes)
                        VALUES (?, ?, 'stop', ?, ?, ?)
                    ")->execute([$wo_id, $technician_id, $labor_type ?: null, $elapsed_ms, $notes]);
                } catch (Throwable $inner) {
                    $pdo->prepare("
                        INSERT INTO wo_time_logs (wo_id, technician_id, action, notes)
                        VALUES (?, ?, 'stop', ?)
                    ")->execute([$wo_id, $technician_id, $notes]);
                }
            }
        } else {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM wo_time_logs WHERE wo_id = ?");
            $existing->execute([$wo_id]);
            if ((int)$existing->fetchColumn() === 0) {
                $pdo->prepare("
                    INSERT INTO wo_time_logs (wo_id, technician_id, action, notes)
                    VALUES (?, ?, 'stop', 'Auto-logged on completion')
                ")->execute([$wo_id, $technician_id]);
            }
        }

        $sql = "UPDATE work_orders SET status = 'resolved', actual_end = NOW()" .
               ($resolution_notes !== '' ? ", resolution_notes = ?" : '') .
               " WHERE wo_id = ?";
        $params = $resolution_notes !== '' ? [$resolution_notes, $wo_id] : [$wo_id];
        $pdo->prepare($sql)->execute($params);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    return [
        'success' => true,
        'wo_id' => $wo_id,
        'status' => 'resolved',
        'has_signature' => $signature_path !== 'data:inline',
        'satisfaction' => $signer_satisfaction,
    ];
}

function update_checklist_completion(PDO $pdo, int $wo_id, int $item_id, bool $is_done, ?string $notes = null): bool {
    try {
        $technician_id = $_SESSION['user_id'] ?? null;
        
        if ($is_done) {
            $stmt = $pdo->prepare("
                INSERT INTO wo_checklist_completions (wo_id, item_id, is_done, notes, completed_by, completed_at)
                VALUES (?, ?, 1, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_done = 1, notes = VALUES(notes), completed_by = VALUES(completed_by), completed_at = NOW()
            ");
            return $stmt->execute([$wo_id, $item_id, $notes, $technician_id]);
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM wo_checklist_completions WHERE wo_id = ? AND item_id = ?
            ");
            return $stmt->execute([$wo_id, $item_id]);
        }
    } catch (Throwable $e) {
        tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:update_checklist_completion', 'Failed to update checklist completion', [
            'wo_id' => $wo_id,
            'item_id' => $item_id,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

function auto_verify_checklist_item(PDO $pdo, int $wo_id, string $verification_type): bool {
    try {
        // Use the existing logic to fetch the correct checklist for this work order
        $checklist = get_checklist_for_work_order($pdo, $wo_id);
        
        $item_id = null;
        foreach ($checklist as $item) {
            if ($item['verification_type'] === $verification_type) {
                $item_id = $item['item_id'];
                break;
            }
        }
        
        if (!$item_id) {
            return false;
        }
        
        // Auto-mark the item as complete
        $technician_id = $_SESSION['user_id'] ?? null;
        $stmt = $pdo->prepare("
            INSERT INTO wo_checklist_completions (wo_id, item_id, is_done, completed_by, completed_at)
            VALUES (?, ?, 1, ?, NOW())
            ON DUPLICATE KEY UPDATE is_done = 1, completed_by = VALUES(completed_by), completed_at = NOW()
        ");
        return $stmt->execute([$wo_id, $item_id, $technician_id]);
    } catch (Throwable $e) {
        tech_dbg('H_CHECKLIST', 'modules/technician/functions.php:auto_verify_checklist_item', 'Failed to auto-verify checklist item', [
            'wo_id' => $wo_id,
            'verification_type' => $verification_type,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

function add_work_order_note(PDO $pdo, int $wo_id, string $note_text, bool $is_voice = false, ?string $voice_path = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_notes (wo_id, note_text, is_voice, voice_path, added_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $note_text, $is_voice ? 1 : 0, $voice_path, $_SESSION['user_id']]);
}

function save_work_order_media(PDO $pdo, int $wo_id, string $media_type, string $file_path, string $file_type, int $file_size_kb, ?string $caption = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_media (wo_id, media_type, file_path, file_type, file_size_kb, caption, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $media_type, $file_path, $file_type, $file_size_kb, $caption, $_SESSION['user_id']]);
    
    // AUTO-VERIFY: Determine verification_type from media_type or caption/path keywords
    $verification_type = null;

    if (in_array($media_type, ['photo_before', 'before'])) {
        $verification_type = 'photo_before';
    } elseif (in_array($media_type, ['photo_after', 'after'])) {
        $verification_type = 'photo_after';
    } elseif ($media_type === 'image') {
        // Fallback: inspect caption and path for before/after keywords
        if (stripos($caption ?? '', 'before') !== false || stripos($file_path, 'before') !== false) {
            $verification_type = 'photo_before';
        } elseif (stripos($caption ?? '', 'after') !== false || stripos($file_path, 'after') !== false) {
            $verification_type = 'photo_after';
        }
    }
    
    if ($verification_type) {
        auto_verify_checklist_item($pdo, $wo_id, $verification_type);
    }
}

function save_work_order_part(PDO $pdo, int $wo_id, int $part_id, int $quantity_used, ?string $serial_number = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, serial_number, used_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $part_id, $quantity_used, $serial_number, $_SESSION['user_id']]);

    // Decrement inventory
    $pdo->prepare("UPDATE parts_inventory SET quantity_on_hand = quantity_on_hand - ? WHERE part_id = ?")
        ->execute([$quantity_used, $part_id]);
}

function save_work_order_signoff(PDO $pdo, int $wo_id, string $signer_name, string $signature_path, ?int $satisfaction = null, ?string $feedback = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_signoff (wo_id, signer_name, signature_path, satisfaction, feedback, signed_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE signer_name = VALUES(signer_name), signature_path = VALUES(signature_path),
                               satisfaction = VALUES(satisfaction), feedback = VALUES(feedback)
    ");
    $stmt->execute([$wo_id, $signer_name, $signature_path, $satisfaction, $feedback, $_SESSION['user_id']]);

    // Mark WO as resolved
    $pdo->prepare("UPDATE work_orders SET status = 'resolved', actual_end = NOW() WHERE wo_id = ?")
        ->execute([$wo_id]);
}

function update_work_order_status(PDO $pdo, int $wo_id, string $status): void {
    $update = [];
    $params = [$status, $wo_id];

    if ($status === 'in_progress') {
        $update[] = 'actual_start = NOW()';
    } elseif ($status === 'resolved') {
        $update[] = 'actual_end = NOW()';
    }

    $sql = "UPDATE work_orders SET status = ?" . (count($update) ? ', ' . implode(', ', $update) : '') . " WHERE wo_id = ?";
    $pdo->prepare($sql)->execute($params);
}

function can_complete_work_order(PDO $pdo, int $wo_id): array {
    $errors = [];

    // Check safety checks
    $safety_checks = get_safety_checks_for_work_order($pdo, $wo_id);
    $mandatory_safety = array_filter($safety_checks, fn($s) => $s['is_mandatory']);
    $incomplete_safety = array_filter($mandatory_safety, fn($s) => !$s['is_done']);
    if ($incomplete_safety) {
        $errors[] = 'All mandatory safety checks must be completed';
    }

    // Check checklist
    $checklist = get_checklist_for_work_order($pdo, $wo_id);
    $mandatory_checklist = array_filter($checklist, fn($c) => $c['is_mandatory']);
    $incomplete_checklist = array_filter($mandatory_checklist, fn($c) => !$c['is_done']);
    if ($incomplete_checklist) {
        $errors[] = 'All mandatory checklist items must be completed';
    }

    // Check media (at least one before or after)
    $media = get_work_order_media($pdo, $wo_id);
    $has_media = count($media) > 0;
    if (!$has_media) {
        $errors[] = 'At least one photo or video must be attached';
    }

    // Check signoff
    $signoff = get_work_order_signoff($pdo, $wo_id);
    if (!$signoff) {
        $errors[] = 'Requester signature and satisfaction rating are required';
    }

    // Check time tracking started
    $time_logs = get_time_logs($pdo, $wo_id);
    if (empty($time_logs)) {
        $errors[] = 'Time tracking must be started';
    }

    return $errors;
}

// ── Parts Inventory ─────────────────────────────────────────

function search_parts(PDO $pdo, string $query): array {
    try {
        if (strlen($query) < 2) {
            return [];
        }
        
        $search_term = '%' . $query . '%';
        
        $stmt = $pdo->prepare("
            SELECT 
                part_id,
                part_name,
                part_number,
                description,
                category,
                quantity_on_hand,
                reorder_level,
                unit_price,
                is_active
            FROM parts_inventory
            WHERE is_active = 1
              AND (
                  part_name LIKE ?
                  OR part_number LIKE ?
                  OR description LIKE ?
              )
            ORDER BY 
                CASE 
                    WHEN part_name LIKE CONCAT(?, '%') THEN 1
                    WHEN part_number LIKE CONCAT(?, '%') THEN 2
                    ELSE 3
                END,
                part_name ASC
            LIMIT 20
        ");
        
        $stmt->execute([$search_term, $search_term, $search_term, $query, $query]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_PARTS', 'modules/technician/functions.php:search_parts', 'Failed to search parts', [
            'query' => $query,
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}

function get_low_stock_parts(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                part_id,
                part_name,
                part_number,
                category,
                quantity_on_hand,
                reorder_level,
                quantity_on_hand - reorder_level AS shortage_amount
            FROM parts_inventory
            WHERE is_active = 1
              AND quantity_on_hand <= reorder_level
            ORDER BY shortage_amount ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_PARTS', 'modules/technician/functions.php:get_low_stock_parts', 'Failed to fetch low stock parts', [
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}

function record_part_usage(PDO $pdo, int $wo_id, int $part_id, int $quantity_used, int $technician_id, ?string $serial_number = null): bool {
    try {
        $pdo->beginTransaction();
        
        // Record usage
        $stmt = $pdo->prepare("
            INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, serial_number, used_by, used_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$wo_id, $part_id, $quantity_used, $serial_number, $technician_id]);
        
        // Decrement inventory
        $stmt = $pdo->prepare("
            UPDATE parts_inventory 
            SET quantity_on_hand = quantity_on_hand - ?, 
                updated_at = NOW()
            WHERE part_id = ?
        ");
        $stmt->execute([$quantity_used, $part_id]);
        
        // Audit trail
        $stmt = $pdo->prepare("
            INSERT INTO parts_inventory_audit (part_id, wo_id, action, quantity_change, technician_id, notes)
            VALUES (?, ?, 'usage', ?, ?, CONCAT('Part usage: ', ?, ' units consumed on WO ', ?))
        ");
        $stmt->execute([$part_id, $wo_id, -$quantity_used, $technician_id, $quantity_used, $wo_id]);
        
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        try { $pdo->rollBack(); } catch (Throwable $ex) {}
        tech_dbg('H_PARTS', 'modules/technician/functions.php:record_part_usage', 'Failed to record part usage', [
            'wo_id' => $wo_id,
            'part_id' => $part_id,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}
?>