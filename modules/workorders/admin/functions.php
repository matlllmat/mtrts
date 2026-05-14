<?php
// modules/workorders/admin/functions.php
// Helpers for the skill / category-skill / location-team admin pages.

function get_all_skills(PDO $pdo, bool $active_only = true): array {
    $sql = "SELECT skill_id, skill_code, skill_name, description, is_active
            FROM skills"
         . ($active_only ? " WHERE is_active = 1" : "")
         . " ORDER BY skill_name";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function get_skill_by_id(PDO $pdo, int $id): array|false {
    $s = $pdo->prepare("SELECT * FROM skills WHERE skill_id = ?");
    $s->execute([$id]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

function upsert_skill(PDO $pdo, ?int $id, string $code, string $name, ?string $desc, int $is_active): int {
    if ($id) {
        $pdo->prepare("UPDATE skills SET skill_code=?, skill_name=?, description=?, is_active=? WHERE skill_id=?")
            ->execute([$code, $name, $desc, $is_active, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO skills (skill_code, skill_name, description, is_active) VALUES (?,?,?,?)")
        ->execute([$code, $name, $desc, $is_active]);
    return (int)$pdo->lastInsertId();
}

function delete_skill(PDO $pdo, int $id): void {
    $pdo->prepare("DELETE FROM skills WHERE skill_id = ?")->execute([$id]);
}

/**
 * Returns the full tech × skill matrix.
 * Output: rows of [user_id, full_name, skill_id, proficiency] for each existing pair,
 * plus the list of technicians and skills for rendering the grid.
 */
function get_technician_skill_matrix(PDO $pdo): array {
    $techs = $pdo->query("
        SELECT u.user_id, u.full_name, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.role_id IN (3, 4) AND u.is_active = 1
        ORDER BY u.full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $skills = get_all_skills($pdo);

    $pairs = $pdo->query("SELECT user_id, skill_id, proficiency FROM technician_skills")
                  ->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($pairs as $p) {
        $map[(int)$p['user_id']][(int)$p['skill_id']] = (int)$p['proficiency'];
    }

    return ['technicians' => $techs, 'skills' => $skills, 'map' => $map];
}

/** Replaces a technician's skill set. `$skills` is [skill_id => proficiency]. */
function set_technician_skills(PDO $pdo, int $user_id, array $skills): void {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM technician_skills WHERE user_id = ?")->execute([$user_id]);

        if ($skills) {
            $ins = $pdo->prepare("INSERT INTO technician_skills (user_id, skill_id, proficiency) VALUES (?,?,?)");
            foreach ($skills as $skill_id => $prof) {
                $prof = max(1, min(3, (int)$prof));
                $ins->execute([$user_id, (int)$skill_id, $prof]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_category_skill_matrix(PDO $pdo): array {
    $cats   = $pdo->query("SELECT category_id, category_name FROM asset_categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
    $skills = get_all_skills($pdo);
    $pairs  = $pdo->query("SELECT category_id, skill_id, is_required FROM category_skills")->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($pairs as $p) {
        $map[(int)$p['category_id']][(int)$p['skill_id']] = (int)$p['is_required'];
    }
    return ['categories' => $cats, 'skills' => $skills, 'map' => $map];
}

function set_category_skills(PDO $pdo, int $category_id, array $skills): void {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM category_skills WHERE category_id = ?")->execute([$category_id]);
        if ($skills) {
            $ins = $pdo->prepare("INSERT INTO category_skills (category_id, skill_id, is_required) VALUES (?,?,?)");
            foreach ($skills as $skill_id => $is_required) {
                $ins->execute([$category_id, (int)$skill_id, (int)$is_required ? 1 : 0]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_location_team_matrix(PDO $pdo): array {
    $techs = $pdo->query("
        SELECT u.user_id, u.full_name
        FROM users u
        WHERE u.role_id IN (3, 4) AND u.is_active = 1
        ORDER BY u.full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Buildings (distinct from locations) for "building-level" assignments
    $buildings = $pdo->query("SELECT DISTINCT building FROM locations ORDER BY building")->fetchAll(PDO::FETCH_COLUMN);

    $assignments = $pdo->query("
        SELECT la.assignment_id, la.user_id, la.location_id, la.building, la.is_primary,
               u.full_name, l.building AS loc_building, l.floor, l.room
        FROM location_assignments la
        LEFT JOIN users u     ON la.user_id     = u.user_id
        LEFT JOIN locations l ON la.location_id = l.location_id
        ORDER BY u.full_name, la.building, l.room
    ")->fetchAll(PDO::FETCH_ASSOC);

    return ['technicians' => $techs, 'buildings' => $buildings, 'assignments' => $assignments];
}

function add_location_assignment(PDO $pdo, int $user_id, ?int $location_id, ?string $building, int $is_primary): void {
    $location_id = $location_id ?: null;
    $building = $building ?: null;
    if (!$location_id && !$building) return;
    $pdo->prepare("
        INSERT IGNORE INTO location_assignments (user_id, location_id, building, is_primary)
        VALUES (?,?,?,?)
    ")->execute([$user_id, $location_id, $building, $is_primary]);
}

function delete_location_assignment(PDO $pdo, int $assignment_id): void {
    $pdo->prepare("DELETE FROM location_assignments WHERE assignment_id = ?")->execute([$assignment_id]);
}

function get_all_locations_list(PDO $pdo): array {
    return $pdo->query("SELECT location_id, building, floor, room FROM locations ORDER BY building, floor, room")
               ->fetchAll(PDO::FETCH_ASSOC);
}
