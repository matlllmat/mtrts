<?php
// modules/reports/functions.php
// SLA Engine, Analytics, and Audit Log logic for MTRTS

/**
 * ── SLA CLOCK ENGINE ──────────────────────────────────────────
 * Handles the complex math of calculating deadlines while
 * respecting business hours and holidays.
 */

/**
 * Fetches business hours from the database
 */
function get_business_hours(PDO $pdo): array {
    $stmt = $pdo->query("SELECT day_of_week, start_time, end_time, is_working FROM business_hours");
    $hours = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hours[$row['day_of_week']] = $row;
    }
    return $hours;
}

/**
 * Fetches holidays for a specific year
 */
function get_holidays(PDO $pdo, int $year): array {
    $stmt = $pdo->prepare("SELECT holiday_date, is_recurring FROM holidays");
    $stmt->execute();
    $holidays = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['is_recurring']) {
            // Convert recurring holiday to current year
            $date = new DateTime($row['holiday_date']);
            $holidays[] = $year . '-' . $date->format('m-d');
        } else {
            $holidays[] = $row['holiday_date'];
        }
    }
    return $holidays;
}

/**
 * The "Brain" - Calculates a deadline by skipping non-working time
 */
function calculate_sla_deadline(PDO $pdo, string $start_time, int $minutes, bool $use_business_hours = true): string {
    $current = new DateTime($start_time);
    
    if (!$use_business_hours) {
        $current->modify("+$minutes minutes");
        return $current->format('Y-m-d H:i:s');
    }

    $biz_hours = get_business_hours($pdo);
    $holidays = get_holidays($pdo, (int)$current->format('Y'));
    $remaining_minutes = $minutes;

    while ($remaining_minutes > 0) {
        $dow = (int)$current->format('w'); // 0=Sun, 6=Sat
        $date_str = $current->format('Y-m-d');
        
        // Is today a working day?
        $is_working_day = isset($biz_hours[$dow]) && $biz_hours[$dow]['is_working'] && !in_array($date_str, $holidays);

        if (!$is_working_day) {
            // Skip to start of next day
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
            continue;
        }

        $start_of_work = new DateTime($date_str . ' ' . $biz_hours[$dow]['start_time']);
        $end_of_work = new DateTime($date_str . ' ' . $biz_hours[$dow]['end_time']);

        // If currently before work starts, jump to start of work
        if ($current < $start_of_work) {
            $current = clone $start_of_work;
        }

        // If currently after work ends, jump to next day
        if ($current >= $end_of_work) {
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
            continue;
        }

        // How many minutes left in today's window?
        $diff = $end_of_work->getTimestamp() - $current->getTimestamp();
        $available_minutes = floor($diff / 60);

        if ($remaining_minutes <= $available_minutes) {
            // We can finish today!
            $current->modify("+$remaining_minutes minutes");
            $remaining_minutes = 0;
        } else {
            // Use up today and move to tomorrow
            $remaining_minutes -= $available_minutes;
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
        }
    }

    return $current->format('Y-m-d H:i:s');
}

/**
 * ── TICKET SLA INITIALIZATION ────────────────────────────────
 */

/**
 * Initializes a new SLA record for a ticket
 */
function init_ticket_sla(PDO $pdo, int $ticket_id): void {
    // 1. Get ticket details (priority, category, event support)
    $stmt = $pdo->prepare("SELECT priority, category_id, is_event_support, created_at FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$t) return;

    // 2. Find matching policy
    $policy = null;
    
    // Check Event Support first
    if ($t['is_event_support']) {
        $stmt = $pdo->query("SELECT * FROM sla_policies WHERE is_event_support = 1 LIMIT 1");
        $policy = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Otherwise match by priority/category
    if (!$policy) {
        $stmt = $pdo->prepare("SELECT * FROM sla_policies WHERE priority = ? AND is_active = 1 ORDER BY category_id DESC LIMIT 1");
        $stmt->execute([$t['priority']]);
        $policy = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$policy) return;

    // 3. Calculate deadlines
    $resp_due = calculate_sla_deadline($pdo, $t['created_at'], $policy['response_minutes'], $policy['uses_business_hours']);
    $diag_due = calculate_sla_deadline($pdo, $t['created_at'], $policy['diagnosis_minutes'], $policy['uses_business_hours']);
    $res_due  = calculate_sla_deadline($pdo, $t['created_at'], $policy['resolution_minutes'], $policy['uses_business_hours']);

    // 4. Save to ticket_sla
    $stmt = $pdo->prepare("
        INSERT INTO ticket_sla 
            (ticket_id, policy_id, response_due, diagnosis_due, resolution_due)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$ticket_id, $policy['policy_id'], $resp_due, $diag_due, $res_due]);
}

/**
 * Updates SLA actual timestamps based on ticket status
 */
function update_ticket_sla_actuals(PDO $pdo, int $ticket_id, string $status): void {
    // 1. Handle unpausing if coming off on_hold
    if ($status !== 'on_hold') {
        $stmt = $pdo->prepare("SELECT paused_at FROM ticket_sla WHERE ticket_id = ? AND paused_at IS NOT NULL");
        $stmt->execute([$ticket_id]);
        $paused_at = $stmt->fetchColumn();
        
        if ($paused_at) {
            $pdo->prepare("
                UPDATE ticket_sla 
                SET 
                    total_paused_minutes = total_paused_minutes + TIMESTAMPDIFF(MINUTE, paused_at, NOW()),
                    response_due = DATE_ADD(response_due, INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    diagnosis_due = DATE_ADD(diagnosis_due, INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    resolution_due = DATE_ADD(resolution_due, INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    paused_at = NULL
                WHERE ticket_id = ?
            ")->execute([$ticket_id]);
        }
    }

    // 2. Handle actual completion timestamps or pausing
    if ($status === 'assigned') {
        $pdo->prepare("UPDATE ticket_sla SET responded_at = NOW() WHERE ticket_id = ? AND responded_at IS NULL")->execute([$ticket_id]);
    } elseif ($status === 'in_progress') {
        $pdo->prepare("UPDATE ticket_sla SET diagnosed_at = NOW() WHERE ticket_id = ? AND diagnosed_at IS NULL")->execute([$ticket_id]);
    } elseif ($status === 'resolved' || $status === 'closed') {
        $pdo->prepare("UPDATE ticket_sla SET resolved_at = NOW() WHERE ticket_id = ? AND resolved_at IS NULL")->execute([$ticket_id]);
    } elseif ($status === 'on_hold') {
        $pdo->prepare("UPDATE ticket_sla SET paused_at = NOW() WHERE ticket_id = ? AND paused_at IS NULL")->execute([$ticket_id]);
    }
}

/**
 * Escalations & Breach Warnings
 */
function check_and_trigger_sla_breaches(PDO $pdo): void {
    if (file_exists(__DIR__ . '/../notifications/functions.php')) {
        require_once __DIR__ . '/../notifications/functions.php';
    }
    
    // Warn about upcoming resolution breaches (next 30 mins)
    $stmt = $pdo->query("
        SELECT t.ticket_id, t.assigned_to, ts.resolution_due, t.ticket_number
        FROM ticket_sla ts
        JOIN tickets t ON ts.ticket_id = t.ticket_id
        WHERE ts.resolved_at IS NULL 
          AND ts.is_resolution_breached = 0
          AND ts.resolution_due > NOW() 
          AND ts.resolution_due <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
          AND ts.paused_at IS NULL
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['assigned_to'] && function_exists('notify_user')) {
            notify_user($pdo, $row['assigned_to'], "SLA Breach Warning", "Ticket {$row['ticket_number']} is about to breach SLA resolution time.", "/mtrts/modules/tickets/view.php?id={$row['ticket_id']}");
        }
    }
    
    // Mark actual breaches
    $pdo->query("UPDATE ticket_sla SET is_response_breached = 1 WHERE responded_at IS NULL AND response_due < NOW() AND is_response_breached = 0");
    $pdo->query("UPDATE ticket_sla SET is_resolution_breached = 1 WHERE resolved_at IS NULL AND resolution_due < NOW() AND is_resolution_breached = 0");
}

/**
 * ── ANALYTICS QUERIES ─────────────────────────────────────────
 */

/**
 * Calculates SLA Compliance Rate
 */
function get_sla_compliance_stats(PDO $pdo, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(t.ticket_id) as total_tickets,
            SUM(CASE WHEN ts.is_response_breached = 0 THEN 1 ELSE 0 END) as met_response,
            SUM(CASE WHEN ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL THEN 1 ELSE 0 END) as met_resolution,
            ROUND(
                (SUM(CASE WHEN ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL THEN 1 ELSE 0 END) / 
                NULLIF(COUNT(t.ticket_id), 0)) * 100, 
            2) as compliance_rate
        FROM tickets t
        LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
        WHERE t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Mean Time To Repair (MTTR) - Average time from Open to Resolved
 */
function get_mttr_stats(PDO $pdo, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT 
            AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_mttr_minutes,
            MIN(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as min_mttr_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as max_mttr_minutes
        FROM tickets
        WHERE status IN ('resolved', 'closed')
          AND resolved_at IS NOT NULL
          AND created_at >= ? AND created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * First Time Fix Rate (FTFR) and Backlog
 */
function get_operational_stats(PDO $pdo, string $start_date, string $end_date): array {
    // FTFR: Resolved tickets that only required 1 work order
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT t.ticket_id) as total_resolved,
            SUM(CASE WHEN (SELECT COUNT(*) FROM work_orders w WHERE w.ticket_id = t.ticket_id) <= 1 THEN 1 ELSE 0 END) as ftfr_count
        FROM tickets t
        WHERE t.status IN ('resolved', 'closed')
          AND t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $ftfr_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $ftfr_rate = !empty($ftfr_data['total_resolved']) ? round(($ftfr_data['ftfr_count'] / $ftfr_data['total_resolved']) * 100, 1) : 0;

    // Total Tickets in range
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE created_at >= ? AND created_at <= ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $total_tickets = $stmt->fetchColumn();

    // Backlog: All currently open tickets
    $backlog = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();

    return [
        'ftfr_rate' => $ftfr_rate,
        'backlog' => $backlog,
        'total_tickets' => $total_tickets
    ];
}

/**
 * Resolution Trends (Chart Data)
 */
function get_resolution_trends(PDO $pdo, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(resolved_at) as resolve_date,
            COUNT(*) as ticket_count
        FROM tickets
        WHERE status IN ('resolved', 'closed')
          AND resolved_at IS NOT NULL
          AND resolved_at >= ? AND resolved_at <= ?
        GROUP BY DATE(resolved_at)
        ORDER BY resolve_date ASC
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Identifies Asset Hotspots (Most problematic equipment)
 */
function get_asset_hotspots(PDO $pdo, int $limit = 10): array {
    return $pdo->query("
        SELECT 
            a.asset_tag, a.model, c.category_name, 
            COUNT(t.ticket_id) as ticket_count,
            MAX(t.created_at) as last_reported
        FROM tickets t
        JOIN assets a ON t.asset_id = a.asset_id
        JOIN asset_categories c ON a.category_id = c.category_id
        GROUP BY a.asset_id
        ORDER BY ticket_count DESC
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Technician Scorecards
 */
function get_technician_scorecards(PDO $pdo): array {
    return $pdo->query("
        SELECT 
            u.full_name,
            COUNT(w.wo_id) as total_jobs,
            SUM(w.status = 'closed') as completed_jobs,
            AVG(TIMESTAMPDIFF(MINUTE, w.actual_start, w.actual_end)) as avg_labor_time,
            AVG(s.satisfaction) as avg_rating
        FROM users u
        JOIN work_orders w ON u.user_id = w.assigned_to
        LEFT JOIN wo_signoff s ON w.wo_id = s.wo_id
        WHERE u.role_id = 4
        GROUP BY u.user_id
        ORDER BY avg_rating DESC, completed_jobs DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ── AUDIT LOGS ────────────────────────────────────────────────
 */

function get_audit_logs(PDO $pdo, array $f = [], int $page = 1, int $per = 20): array {
    $where = ["1=1"];
    $params = [];
    
    if (!empty($f['user_id'])) {
        $where[] = "user_id = ?";
        $params[] = $f['user_id'];
    }
    if (!empty($f['object_type'])) {
        $where[] = "object_type = ?";
        $params[] = $f['object_type'];
    }
    if (!empty($f['date_from'])) {
        $where[] = "l.created_at >= ?";
        $params[] = $f['date_from'] . ' 00:00:00';
    }
    if (!empty($f['date_to'])) {
        $where[] = "l.created_at <= ?";
        $params[] = $f['date_to'] . ' 23:59:59';
    }

    $where_str = implode(" AND ", $where);
    $offset = ($page - 1) * $per;

    $stmt = $pdo->prepare("
        SELECT l.*, u.full_name as user_name
        FROM audit_log l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE $where_str
        ORDER BY l.created_at DESC
        LIMIT $per OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PII Masking for non-admins
    if (session_status() === PHP_SESSION_NONE) session_start();
    $is_admin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    
    if (!$is_admin) {
        foreach ($logs as &$log) {
            if (!empty($log['new_values'])) {
                $log['new_values'] = mask_pii($log['new_values']);
            }
        }
    }
    return $logs;
}

/**
 * Helper to mask PII in strings
 */
function mask_pii(string $str): string {
    // Mask emails
    $str = preg_replace('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', '***@***.***', $str);
    // Mask phone numbers
    $str = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', '***-***-****', $str);
    return $str;
}

/**
 * ── DRILL-DOWN QUERIES ──────────────────────────────────────────
 */
function get_drilldown_tickets(PDO $pdo, string $type, string $start_date, string $end_date): array {
    $base_query = "
        SELECT 
            t.ticket_id, t.ticket_number, t.priority, c.category_name, 
            t.status, t.created_at, ts.resolution_due, u.full_name as requester
        FROM tickets t
        LEFT JOIN asset_categories c ON t.category_id = c.category_id
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
        WHERE 1=1
    ";
    
    $params = [];
    
    switch ($type) {
        case 'backlog':
            $base_query .= " AND t.status NOT IN ('resolved', 'closed')";
            break;
            
        case 'ftfr':
            $base_query .= " AND t.status IN ('resolved', 'closed') 
                             AND t.created_at >= ? AND t.created_at <= ?
                             AND (SELECT COUNT(*) FROM work_orders w WHERE w.ticket_id = t.ticket_id) <= 1";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'mttr':
            $base_query .= " AND t.status IN ('resolved', 'closed') 
                             AND t.resolved_at IS NOT NULL
                             AND t.created_at >= ? AND t.created_at <= ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'resolved':
            $base_query .= " AND t.status IN ('resolved', 'closed') 
                             AND t.resolved_at IS NOT NULL
                             AND t.resolved_at >= ? AND t.resolved_at <= ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'breaches':
            $base_query .= " AND t.created_at >= ? AND t.created_at <= ?
                             AND (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1 OR ts.sla_id IS NULL)";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'event_support':
            $base_query .= " AND t.is_event_support = 1 AND t.status NOT IN ('resolved', 'closed')";
            break;
            
        case 'total':
        default:
            $base_query .= " AND t.created_at >= ? AND t.created_at <= ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
    }
    
    $base_query .= " ORDER BY t.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($base_query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Location Heatmap: Building/Room with most tickets
 */
function get_location_heatmap(PDO $pdo): array {
    return $pdo->query("
        SELECT l.building, l.room, COUNT(t.ticket_id) as ticket_count
        FROM tickets t
        JOIN locations l ON t.location_id = l.location_id
        GROUP BY l.location_id
        ORDER BY ticket_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Warranty Exposure: Assets with warranty expiring in next 90 days
 */
function get_warranty_exposure(PDO $pdo): array {
    return $pdo->query("
        SELECT a.asset_tag, a.model, a.manufacturer, w.warranty_end as warranty_expiry
        FROM assets a
        JOIN asset_warranty w ON a.asset_id = w.asset_id
        WHERE w.warranty_end >= CURDATE()
          AND w.warranty_end <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY w.warranty_end ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Active Escalations: Breached tickets that are still open
 */
function get_active_escalations(PDO $pdo): array {
    return $pdo->query("
        SELECT t.ticket_id, t.ticket_number, u.full_name as assignee,
               ts.is_response_breached, ts.is_resolution_breached,
               CASE 
                 WHEN ts.is_resolution_breached = 1 THEN ts.resolution_due 
                 ELSE ts.response_due 
               END as deadline
        FROM ticket_sla ts
        JOIN tickets t ON ts.ticket_id = t.ticket_id
        LEFT JOIN users u ON t.assigned_to = u.user_id
        WHERE (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1)
          AND t.status NOT IN ('resolved', 'closed', 'cancelled')
        ORDER BY deadline ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}
