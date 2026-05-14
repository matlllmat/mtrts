<?php
// modules/workorders/admin/save.php
// Shared POST handler for skill / tech-skill / category-skill / location-team admin pages.
// Dispatches on $_POST['action'].

$module = 'workorders';
require_once __DIR__ . '/../../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: skills.php');
    exit;
}

// CSRF check
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? '';
$flash   = null;

try {
    switch ($action) {

        // ── SKILLS CRUD ─────────────────────────────────────
        case 'skill_save': {
            $id   = (int)($_POST['skill_id'] ?? 0) ?: null;
            $code = trim($_POST['skill_code'] ?? '');
            $name = trim($_POST['skill_name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;
            $act  = isset($_POST['is_active']) ? 1 : 0;
            if ($code === '' || $name === '') {
                $flash = ['type' => 'error', 'msg' => 'Skill code and name are required.'];
                break;
            }
            upsert_skill($pdo, $id, $code, $name, $desc, $act);
            $flash = ['type' => 'ok', 'msg' => 'Skill saved.'];
            header('Location: skills.php?ok=1');
            $_SESSION['admin_flash'] = $flash;
            exit;
        }

        case 'skill_delete': {
            $id = (int)($_POST['skill_id'] ?? 0);
            if ($id > 0) delete_skill($pdo, $id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Skill deleted.'];
            header('Location: skills.php');
            exit;
        }

        // ── TECHNICIAN × SKILL MATRIX ───────────────────────
        case 'tech_skills_save': {
            $tech_id = (int)($_POST['user_id'] ?? 0);
            if ($tech_id < 1) {
                $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Technician is required.'];
                header('Location: technician_skills.php');
                exit;
            }
            // Posted format: skills[skill_id] = proficiency (or absent if unchecked)
            $skills = [];
            foreach ($_POST['skills'] ?? [] as $sid => $prof) {
                $sid  = (int)$sid;
                $prof = (int)$prof;
                if ($sid > 0 && $prof > 0) $skills[$sid] = $prof;
            }
            set_technician_skills($pdo, $tech_id, $skills);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Technician skills updated.'];
            header('Location: technician_skills.php?user_id=' . $tech_id);
            exit;
        }

        // ── CATEGORY × SKILL MATRIX ─────────────────────────
        case 'category_skills_save': {
            $cat_id = (int)($_POST['category_id'] ?? 0);
            if ($cat_id < 1) {
                $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Category is required.'];
                header('Location: category_skills.php');
                exit;
            }
            // Posted format: skills[skill_id] = 1 (required) — absent skill_id means not linked
            $skills = [];
            foreach ($_POST['skills'] ?? [] as $sid => $val) {
                $sid = (int)$sid;
                if ($sid > 0) $skills[$sid] = 1;
            }
            set_category_skills($pdo, $cat_id, $skills);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Category skills updated.'];
            header('Location: category_skills.php?category_id=' . $cat_id);
            exit;
        }

        // ── LOCATION TEAMS ──────────────────────────────────
        case 'location_add': {
            $uid       = (int)($_POST['user_id'] ?? 0);
            $loc_id    = (int)($_POST['location_id'] ?? 0) ?: null;
            $building  = trim($_POST['building'] ?? '') ?: null;
            $primary   = isset($_POST['is_primary']) ? 1 : 0;
            if ($uid < 1 || (!$loc_id && !$building)) {
                $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Technician and a building or room are required.'];
                header('Location: location_teams.php');
                exit;
            }
            add_location_assignment($pdo, $uid, $loc_id, $building, $primary);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Location assignment added.'];
            header('Location: location_teams.php');
            exit;
        }

        case 'location_delete': {
            $aid = (int)($_POST['assignment_id'] ?? 0);
            if ($aid > 0) delete_location_assignment($pdo, $aid);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Location assignment removed.'];
            header('Location: location_teams.php');
            exit;
        }

        default:
            http_response_code(400);
            exit('Unknown action.');
    }
} catch (Throwable $e) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Save failed: ' . $e->getMessage()];
    $back = $_SERVER['HTTP_REFERER'] ?? 'skills.php';
    header('Location: ' . $back);
    exit;
}
