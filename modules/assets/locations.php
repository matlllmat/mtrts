<?php
// modules/assets/locations.php — Manage locations (rooms).
// Logic only: guard, data fetch, then hands off to the view.

$module = 'assets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$locations = get_locations_with_counts($pdo);

// Build a building → unique floors map so the form can suggest existing
// floors filtered by the chosen building. Free-text is still allowed.
$bld_floor_map = [];
foreach ($locations as $_loc) {
    $bld_floor_map[$_loc['building']][$_loc['floor']] = true;
}
foreach ($bld_floor_map as $_b => $_floors) {
    $bld_floor_map[$_b] = array_keys($_floors);
}
unset($_loc, $_b, $_floors);

// Pull any flash-back form state (from a failed save round-trip)
$form_data   = $_SESSION['loc_form_data']   ?? [];
$form_errors = $_SESSION['loc_form_errors'] ?? [];
$flash_error = $_SESSION['loc_flash_error'] ?? '';
$flash_ok    = $_SESSION['loc_flash_ok']    ?? '';
unset(
    $_SESSION['loc_form_data'],
    $_SESSION['loc_form_errors'],
    $_SESSION['loc_flash_error'],
    $_SESSION['loc_flash_ok']
);

// If editing a single location, prefill the form
$edit_id  = (int)($_GET['edit'] ?? 0);
$edit_loc = $edit_id ? get_location_by_id($pdo, $edit_id) : false;

require __DIR__ . '/locations.view.php';
require_once __DIR__ . '/../../includes/footer.php';
