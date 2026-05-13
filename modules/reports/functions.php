<?php
// modules/reports/functions.php
// SLA Engine, Analytics, and Audit Log logic for MTRTS

require_once __DIR__ . '/../../config/sla.php';

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
            -- Met: Resolved on time
            SUM(CASE WHEN t.status IN ('resolved', 'closed') AND ts.is_resolution_breached = 0 THEN 1 ELSE 0 END) as met_resolution,
            -- Denominator: All resolved with SLA + Open breaches
            SUM(CASE WHEN (t.status IN ('resolved', 'closed') AND ts.sla_id IS NOT NULL) OR (t.status NOT IN ('resolved', 'closed', 'cancelled') AND ts.is_resolution_breached = 1) THEN 1 ELSE 0 END) as relevant_tickets,
            ROUND(
                (SUM(CASE WHEN t.status IN ('resolved', 'closed') AND ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL THEN 1 ELSE 0 END) / 
                NULLIF(SUM(CASE WHEN (t.status IN ('resolved', 'closed') AND ts.sla_id IS NOT NULL) OR (t.status NOT IN ('resolved', 'closed', 'cancelled') AND ts.is_resolution_breached = 1) THEN 1 ELSE 0 END), 0)) * 100, 
            2) as compliance_rate
        FROM tickets t
        LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
        WHERE t.created_at >= ? AND t.created_at <= ?
          AND t.status != 'cancelled'
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Mean Time To Repair (MTTR) - Average time from Open to Resolved
 */
function get_mttr_stats(PDO $pdo, string $start_date, string $end_date): array {
    // MTTR = average ACTUAL labor time from wo_time_logs (elapsed_ms), not wall-clock ticket age
    $stmt = $pdo->prepare("
        SELECT 
            AVG(labor_minutes) as avg_mttr_minutes,
            MIN(labor_minutes) as min_mttr_minutes,
            MAX(labor_minutes) as max_mttr_minutes
        FROM (
            SELECT 
                t.ticket_id,
                COALESCE(SUM(tl.elapsed_ms), 0) / 60000.0 as labor_minutes
            FROM tickets t
            JOIN work_orders wo ON wo.ticket_id = t.ticket_id
            LEFT JOIN wo_time_logs tl ON tl.wo_id = wo.wo_id AND tl.action = 'stop'
            WHERE t.status IN ('resolved', 'closed')
              AND t.resolved_at IS NOT NULL
              AND t.created_at >= ? AND t.created_at <= ?
            GROUP BY t.ticket_id
        ) as labor_per_ticket
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
          AND t.resolved_at >= ? AND t.resolved_at <= ?
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
            COUNT(DISTINCT w.wo_id) as total_jobs,
            SUM(w.status IN ('resolved', 'closed')) as completed_jobs,
            COALESCE(SUM(tl.elapsed_ms), 0) / 60000.0 as avg_labor_time,
            AVG(s.satisfaction) as avg_rating
        FROM users u
        JOIN work_orders w ON u.user_id = w.assigned_to
        LEFT JOIN wo_time_logs tl ON tl.wo_id = w.wo_id AND tl.action = 'stop'
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
        $where[] = "l.user_id = ?";
        $params[] = $f['user_id'];
    }
    if (!empty($f['object_type'])) {
        $where[] = "l.object_type = ?";
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
    if (!empty($f['q'])) {
        $kw = '%' . $f['q'] . '%';
        $where[] = "(l.action LIKE ? OR l.object_type LIKE ? OR l.old_values LIKE ? OR l.new_values LIKE ? OR u.full_name LIKE ? OR l.ip_address LIKE ?)";
        array_push($params, $kw, $kw, $kw, $kw, $kw, $kw);
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

function count_audit_logs(PDO $pdo, array $f = []): int {
    $where = ["1=1"];
    $params = [];

    if (!empty($f['user_id'])) {
        $where[] = "l.user_id = ?";
        $params[] = $f['user_id'];
    }
    if (!empty($f['object_type'])) {
        $where[] = "l.object_type = ?";
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
    if (!empty($f['q'])) {
        $kw = '%' . $f['q'] . '%';
        $where[] = "(l.action LIKE ? OR l.object_type LIKE ? OR l.old_values LIKE ? OR l.new_values LIKE ? OR u.full_name LIKE ? OR l.ip_address LIKE ?)";
        array_push($params, $kw, $kw, $kw, $kw, $kw, $kw);
    }

    $where_str = implode(" AND ", $where);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM audit_log l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE $where_str
    ");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * ── DRILL-DOWN QUERIES ──────────────────────────────────────────
 */
function get_drilldown_tickets(PDO $pdo, string $type, string $start_date, string $end_date): array {
    $base_query = "
        SELECT 
            t.ticket_id, t.ticket_number, t.priority, c.category_name, 
            t.status, t.created_at, ts.resolution_due, u.full_name as requester,
            (SELECT wo_number FROM work_orders WHERE ticket_id = t.ticket_id ORDER BY wo_id DESC LIMIT 1) as wo_number
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
                             AND t.resolved_at >= ? AND t.resolved_at <= ?
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
            $subtype = $_GET['subtype'] ?? 'all';
            $base_query .= " AND t.created_at >= ? AND t.created_at <= ? AND t.status != 'cancelled'";
            
            if ($subtype === 'breached') {
                // Resolved but Breached
                $base_query .= " AND t.status IN ('resolved', 'closed') AND ts.is_resolution_breached = 1";
            } elseif ($subtype === 'open_breached') {
                // Breached but not yet done
                $base_query .= " AND t.status NOT IN ('resolved', 'closed') AND ts.is_resolution_breached = 1";
            } elseif ($subtype === 'not_done') {
                // Not done workorders (Open and NOT Breached)
                $base_query .= " AND t.status NOT IN ('resolved', 'closed') AND ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL";
            } elseif ($subtype === 'no_deadline') {
                $base_query .= " AND ts.sla_id IS NULL";
            } else {
                // All Non-Compliant (Strictly Breaches only)
                $base_query .= " AND ts.is_resolution_breached = 1";
            }
            
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
        SELECT l.building, COUNT(t.ticket_id) as ticket_count,
               GROUP_CONCAT(DISTINCT l.room SEPARATOR ', ') as rooms
        FROM tickets t
        JOIN locations l ON t.location_id = l.location_id
        GROUP BY l.building
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

/**
 * ── TICKET AGING ──────────────────────────────────────────────
 * Groups open tickets into aging buckets: 0-7d, 8-14d, 15-30d, 30d+
 */
function get_ticket_aging(PDO $pdo): array {
    return $pdo->query("
        SELECT 
            SUM(DATEDIFF(NOW(), created_at) BETWEEN 0 AND 7) as bucket_0_7,
            SUM(DATEDIFF(NOW(), created_at) BETWEEN 8 AND 14) as bucket_8_14,
            SUM(DATEDIFF(NOW(), created_at) BETWEEN 15 AND 30) as bucket_15_30,
            SUM(DATEDIFF(NOW(), created_at) > 30) as bucket_over_30,
            COUNT(*) as total_open,
            ROUND(AVG(DATEDIFF(NOW(), created_at)), 1) as avg_age_days
        FROM tickets
        WHERE status NOT IN ('resolved', 'closed', 'cancelled')
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * ── TIME HEATMAP ─────────────────────────────────────────────
 * Ticket volume by hour-of-day and day-of-week (all-time).
 */
function get_time_heatmap(PDO $pdo): array {
    $by_hour = $pdo->query("
        SELECT HOUR(created_at) AS hour, COUNT(*) AS count
        FROM tickets
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $by_dow = $pdo->query("
        SELECT DAYOFWEEK(created_at) AS dow, COUNT(*) AS count
        FROM tickets
        GROUP BY DAYOFWEEK(created_at)
        ORDER BY dow ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Fill missing hours/days with 0 so the chart always has 24 / 7 points
    $hours = array_fill(0, 24, 0);
    foreach ($by_hour as $r) $hours[(int)$r['hour']] = (int)$r['count'];

    $days = array_fill(1, 7, 0); // DAYOFWEEK: 1=Sun … 7=Sat
    foreach ($by_dow as $r) $days[(int)$r['dow']] = (int)$r['count'];

    return [
        'by_hour' => $hours, // indexed 0–23
        'by_dow'  => array_values($days), // 0=Sun … 6=Sat
    ];
}

/**
 * ── COST PER TICKET / ASSET ──────────────────────────────────
 * Aggregates parts + estimated labor cost from work orders.
 */
function get_cost_stats(PDO $pdo, string $start_date, string $end_date): array {
    if (!defined('LABOR_RATE_PER_HOUR')) define('LABOR_RATE_PER_HOUR', 200.00);

    // Parts cost per ticket
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT t.ticket_id) as total_tickets,
            COALESCE(SUM(pu.quantity_used * pi.unit_cost), 0) as total_parts_cost
        FROM tickets t
        LEFT JOIN work_orders w ON t.ticket_id = w.ticket_id
        LEFT JOIN wo_parts_used pu ON w.wo_id = pu.wo_id
        LEFT JOIN parts_inventory pi ON pu.part_id = pi.part_id
        WHERE t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $ticket_cost = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Labor cost: sum elapsed_ms from wo_time_logs for WOs in the date range
    $stmt2 = $pdo->prepare("
        SELECT COALESCE(SUM(tl.elapsed_ms), 0) as total_elapsed_ms
        FROM wo_time_logs tl
        JOIN work_orders w ON tl.wo_id = w.wo_id
        JOIN tickets t ON w.ticket_id = t.ticket_id
        WHERE t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt2->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $labor_ms    = (float)($stmt2->fetchColumn() ?: 0);
    $labor_hours = $labor_ms / 3600000;
    $total_labor = round($labor_hours * LABOR_RATE_PER_HOUR, 2);

    $total_tickets  = (int)($ticket_cost['total_tickets'] ?? 0);
    $total_parts    = (float)($ticket_cost['total_parts_cost'] ?? 0);
    $total_combined = round($total_parts + $total_labor, 2);
    $avg_combined   = $total_tickets > 0 ? round($total_combined / $total_tickets, 2) : 0;

    // Top 5 costliest assets (parts + labor combined)
    // NOTE: MySQL forbids alias references to aggregate functions in ORDER BY arithmetic,
    // so the full expressions are repeated here.
    $costliest = $pdo->query("
        SELECT
            a.asset_tag, a.model,
            COALESCE(SUM(pu.quantity_used * pi.unit_cost), 0) as parts_cost,
            COALESCE(SUM(tl.elapsed_ms), 0) as elapsed_ms,
            COUNT(DISTINCT w.wo_id) as wo_count
        FROM assets a
        JOIN tickets t ON t.asset_id = a.asset_id
        JOIN work_orders w ON t.ticket_id = w.ticket_id
        LEFT JOIN wo_parts_used pu ON w.wo_id = pu.wo_id
        LEFT JOIN parts_inventory pi ON pu.part_id = pi.part_id
        LEFT JOIN wo_time_logs tl ON w.wo_id = tl.wo_id
        GROUP BY a.asset_id
        ORDER BY (
            COALESCE(SUM(pu.quantity_used * pi.unit_cost), 0)
            + (COALESCE(SUM(tl.elapsed_ms), 0) / 3600000) * " . LABOR_RATE_PER_HOUR . "
        ) DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Add computed total_cost to each asset row
    foreach ($costliest as &$row) {
        $row['labor_cost'] = round(((float)$row['elapsed_ms'] / 3600000) * LABOR_RATE_PER_HOUR, 2);
        $row['total_cost'] = round((float)$row['parts_cost'] + $row['labor_cost'], 2);
    }
    unset($row);

    return [
        'total_parts_cost'       => $total_parts,
        'total_labor_cost'       => $total_labor,
        'total_combined_cost'    => $total_combined,
        'avg_cost_per_ticket'    => $avg_combined,
        'labor_rate_per_hour'    => LABOR_RATE_PER_HOUR,
        'costliest_assets'       => $costliest,
    ];
}

