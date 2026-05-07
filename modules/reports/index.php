<?php
$module = 'reports';
$page_title = 'SLA & Performance Analytics';
require_once __DIR__ . '/../../config/guard.php';

// The frontend will load data via AJAX from api_stats.php and api_audit.php
<<<<<<< HEAD
// We just need to load the view.
=======
require_once __DIR__ . '/../../config/db.php';
$sla_policies = $pdo->query("SELECT * FROM sla_policies WHERE is_active = 1 ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low'), policy_id ASC")->fetchAll();
>>>>>>> 0b371872eff460cdb0693a941470db1c568d6a04

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
