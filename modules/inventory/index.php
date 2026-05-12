<?php
// modules/inventory/index.php — Parts inventory landing page.

$module = 'inventory';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$filters = [
    'q'        => trim($_GET['q']        ?? ''),
    'category' => trim($_GET['category'] ?? ''),
    'stock'    => trim($_GET['stock']    ?? ''),
    'sort_col' => trim($_GET['sort_col'] ?? 'part_name'),
    'sort_dir' => trim($_GET['sort_dir'] ?? 'ASC'),
];
$current_page = max(1, (int)($_GET['p'] ?? 1));
$per_page     = 15;

$parts      = list_parts($pdo, $filters, $current_page, $per_page);
$total      = count_parts($pdo, $filters);
$stats      = get_inventory_stats($pdo);
$categories = get_part_categories($pdo);
$alert_n    = count_unacknowledged_alerts($pdo);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
