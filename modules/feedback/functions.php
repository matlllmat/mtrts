<?php
// modules/feedback/functions.php
// Data-access helpers for the Job Completion Feedback module.
// $pdo is provided by guard.php / auth_only.php — never create one here.

// ── Read ──────────────────────────────────────────────────────

/**
 * Fetch the feedback record for a given Work Order.
 *
 * Returns the row as an associative array, or null if no feedback has been
 * submitted yet for that Work Order.
 */
function get_feedback_for_wo(PDO $pdo, int $wo_id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT * FROM wo_feedback WHERE wo_id = ?"
    );
    $stmt->execute([$wo_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

/**
 * Fetch Work Order details needed to render the Feedback_Page.
 *
 * JOINs work_orders → tickets → users (assigned technician) to return:
 *   wo_id, wo_number, ticket_title, requester_id,
 *   assigned_to, assigned_to_name, status
 *
 * Returns null if no matching Work Order is found.
 */
function get_wo_for_feedback(PDO $pdo, int $wo_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            wo.wo_id,
            wo.wo_number,
            t.title          AS ticket_title,
            t.requester_id,
            wo.assigned_to,
            u.full_name      AS assigned_to_name,
            wo.status
        FROM work_orders wo
        JOIN  tickets t ON t.ticket_id = wo.ticket_id
        LEFT JOIN users u ON u.user_id  = wo.assigned_to
        WHERE wo.wo_id = ?
    ");
    $stmt->execute([$wo_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

// ── Write ─────────────────────────────────────────────────────

/**
 * Persist a feedback record for a completed Work Order.
 *
 * Validates that $rating is between 1 and 5 inclusive before touching the
 * database; throws InvalidArgumentException otherwise.
 *
 * The UNIQUE constraint on wo_id in wo_feedback prevents duplicate rows —
 * callers should check get_feedback_for_wo() before calling this function.
 *
 * @throws InvalidArgumentException if $rating is outside the range 1–5.
 */
function save_feedback(
    PDO $pdo,
    int $wo_id,
    int $requester_id,
    int $rating,
    ?string $comment
): void {
    if ($rating < 1 || $rating > 5) {
        throw new InvalidArgumentException(
            "Rating must be between 1 and 5 inclusive; got {$rating}."
        );
    }

    $pdo->prepare("
        INSERT INTO wo_feedback (wo_id, requester_id, rating, comment)
        VALUES (?, ?, ?, ?)
    ")->execute([$wo_id, $requester_id, $rating, $comment]);
}
