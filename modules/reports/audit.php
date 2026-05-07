<?php
// modules/reports/audit.php
$module = 'reports';
$page_title = 'System Audit Logs';
require_once __DIR__ . '/../../config/guard.php';

require __DIR__ . '/audit.view.php';
require_once __DIR__ . '/../../includes/footer.php';
