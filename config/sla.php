<?php
// config/sla.php
// Shared SLA Engine logic for MTRTS

/**
 * The "Brain" - Calculates a deadline by skipping non-working time
 */
function calculate_sla_deadline(PDO $pdo, string $start_time, int $minutes, bool $use_business_hours = true): string {
    $current = new DateTime($start_time);
    
    if (!$use_business_hours) {
        $current->modify("+$minutes minutes");
        return $current->format('Y-m-d H:i:s');
    }

    // Fetch business hours
    $stmt = $pdo->query("SELECT day_of_week, start_time, end_time, is_working FROM business_hours");
    $biz_hours = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $biz_hours[$row['day_of_week']] = $row;
    }

    // Fetch holidays
    $year = (int)$current->format('Y');
    $stmt = $pdo->prepare("SELECT holiday_date, is_recurring FROM holidays");
    $stmt->execute();
    $holidays = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['is_recurring']) {
            $date = new DateTime($row['holiday_date']);
            $holidays[] = $year . '-' . $date->format('m-d');
        } else {
            $holidays[] = $row['holiday_date'];
        }
    }

    $remaining_minutes = $minutes;

    while ($remaining_minutes > 0) {
        $dow = (int)$current->format('w'); // 0=Sun, 6=Sat
        $date_str = $current->format('Y-m-d');
        
        $is_working_day = isset($biz_hours[$dow]) && $biz_hours[$dow]['is_working'] && !in_array($date_str, $holidays);

        if (!$is_working_day) {
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
            continue;
        }

        $start_of_work = new DateTime($date_str . ' ' . $biz_hours[$dow]['start_time']);
        $end_of_work = new DateTime($date_str . ' ' . $biz_hours[$dow]['end_time']);

        if ($current < $start_of_work) {
            $current = clone $start_of_work;
        }

        if ($current >= $end_of_work) {
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
            continue;
        }

        $diff = $end_of_work->getTimestamp() - $current->getTimestamp();
        $available_minutes = floor($diff / 60);

        if ($remaining_minutes <= $available_minutes) {
            $current->modify("+$remaining_minutes minutes");
            $remaining_minutes = 0;
        } else {
            $remaining_minutes -= $available_minutes;
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
        }
    }

    return $current->format('Y-m-d H:i:s');
}

/**
 * Updates SLA actual timestamps and handles PAUSE logic
 */
function update_ticket_sla(PDO $pdo, int $ticket_id, string $status): void {
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
 * Checks for breached tickets and escalates them to on-call or managers
 */
function check_sla_escalations(PDO $pdo): array {
    $breached = [];
    
    // 1. Mark new breaches
    $pdo->query("UPDATE ticket_sla SET is_response_breached = 1 WHERE responded_at IS NULL AND response_due < NOW() AND is_response_breached = 0");
    $pdo->query("UPDATE ticket_sla SET is_resolution_breached = 1 WHERE resolved_at IS NULL AND resolution_due < NOW() AND is_resolution_breached = 0");

    // 2. Fetch escalated tickets (breached but not yet resolved)
    $stmt = $pdo->query("
        SELECT t.ticket_id, t.ticket_number, t.title, t.priority, t.assigned_to, 
               ts.is_response_breached, ts.is_resolution_breached,
               u.full_name as current_assignee
        FROM ticket_sla ts
        JOIN tickets t ON ts.ticket_id = t.ticket_id
        LEFT JOIN users u ON t.assigned_to = u.user_id
        WHERE (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1)
          AND t.status NOT IN ('resolved', 'closed', 'cancelled')
    ");
    $breached = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Trigger hierarchical notifications (Simulated)
    foreach ($breached as $b) {
        // In a real system, we'd check if we already notified for this breach level
        // Level 1: Technician (already notified)
        // Level 2: IT Managers (Role 2)
        $managers = $pdo->query("SELECT user_id FROM users WHERE role_id = 2 AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($managers as $mid) {
            // notify_user($pdo, $mid, "ESCALATION: Ticket " . $b['ticket_number'], "Breached ticket assigned to " . ($b['current_assignee'] ?: 'Unassigned'));
        }
    }
    
    return $breached;
}
