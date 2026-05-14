<?php
// modules/reports/scope.php
// Row-level security helper. Returns the canonical scope used by every
// metric function and the BI API.
//
// Scope keys produced:
//   - location_id      → filter to a specific room
//   - department_id    → filter to requester's department
//   - building         → filter to a specific building
//   - assigned_to      → hard-scope to a single user (technician/it_staff)
//
// Role behavior:
//   1 admin / 8 super_admin → see all by default; respects user-supplied filters.
//   2 it_manager            → defaults to their own department; may override.
//   3 it_staff / 4 tech     → hard-scoped to assigned_to = self.

function resolve_report_scope(PDO $pdo, int $user_id, int $role_id, array $f): array {
    $scope = [];

    // Roles 1 (admin) and 8 (super_admin): pass user filters as-is
    if (in_array($role_id, [1, 8], true)) {
        if (!empty($f['location_id']))   $scope['location_id']   = (int)$f['location_id'];
        if (!empty($f['department_id'])) $scope['department_id'] = (int)$f['department_id'];
        if (!empty($f['building']))      $scope['building']      = trim((string)$f['building']);
        return $scope;
    }

    // Role 2 (it_manager): default to own department; allow override
    if ($role_id === 2) {
        $stmt = $pdo->prepare("SELECT department_id FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $own_dept = (int)$stmt->fetchColumn();

        if (!empty($f['department_id'])) {
            $scope['department_id'] = (int)$f['department_id'];
        } elseif ($own_dept > 0) {
            $scope['department_id'] = $own_dept;
        }
        if (!empty($f['location_id'])) $scope['location_id'] = (int)$f['location_id'];
        if (!empty($f['building']))    $scope['building']    = trim((string)$f['building']);
        return $scope;
    }

    // Roles 3 (it_staff), 4 (technician): hard-scoped to own assignments
    $scope['assigned_to'] = $user_id;
    return $scope;
}

/**
 * Helper used by query builders: append scope-driven WHERE fragments to an
 * existing $where[] / $params[] pair. Assumes the query has these aliases:
 *   t   → tickets
 *   ru  → users (joined via t.requester_id) — caller must ensure the join.
 *   loc → locations (joined via t.location_id) — caller must ensure the join.
 *
 * For functions that work on work_orders aliased as `w`, set $wo_alias='w' so
 * assigned_to is matched against w.assigned_to instead of t.assigned_to.
 */
function apply_scope_where(array $scope, array &$where, array &$params, string $wo_alias = 'w'): void {
    if (!empty($scope['location_id'])) {
        $where[]  = "t.location_id = ?";
        $params[] = (int)$scope['location_id'];
    }
    if (!empty($scope['department_id'])) {
        $where[]  = "ru.department_id = ?";
        $params[] = (int)$scope['department_id'];
    }
    if (!empty($scope['building'])) {
        $where[]  = "loc.building = ?";
        $params[] = $scope['building'];
    }
    if (!empty($scope['assigned_to'])) {
        // Prefer the work_order's assigned_to when a WO alias is in scope,
        // else fall back to the ticket-level assignment.
        $where[]  = "({$wo_alias}.assigned_to = ? OR t.assigned_to = ?)";
        $params[] = (int)$scope['assigned_to'];
        $params[] = (int)$scope['assigned_to'];
    }
}
