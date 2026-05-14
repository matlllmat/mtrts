<?php
// modules/reports/api_audit.php
$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Only admins and IT managers can view audit logs
if (!in_array($_SESSION['role_id'], [1, 2, 8])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'object_type' => $_GET['object_type'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
];

$page = (int)($_GET['page'] ?? 1);
$per = (int)($_GET['per'] ?? 20);

$logs = get_audit_logs($pdo, $filters, $page, $per);

// PII handling for non super_admin (role 8):
// - external_requester_email + password + password_hash → null
// - internal user email + contact_number → masked
if ($_SESSION['role_id'] != 8) {
    $null_fields = ['external_requester_email', 'password', 'password_hash'];
    foreach ($logs as &$log) {
        foreach (['old_values', 'new_values'] as $col) {
            if (empty($log[$col])) continue;
            $vals = json_decode($log[$col], true);
            if (!is_array($vals)) continue;
            foreach ($null_fields as $f) {
                if (array_key_exists($f, $vals)) $vals[$f] = null;
            }
            if (isset($vals['email']))          $vals['email']          = '********@***.***';
            if (isset($vals['contact_number'])) $vals['contact_number'] = '********';
            $log[$col] = json_encode($vals);
        }
    }
}

echo json_encode($logs);
