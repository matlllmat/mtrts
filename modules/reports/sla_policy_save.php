<?php
// modules/reports/sla_policy_save.php — POST handler for SLA policy create/update
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/../../config/sla.php';

if (!in_array($_SESSION['role_id'], [1, 2, 8])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only.']);
    exit;
}

$policy_id    = isset($_POST['policy_id']) && $_POST['policy_id'] !== '' ? (int)$_POST['policy_id'] : null;
$justification = trim($_POST['justification'] ?? '');
$user_id       = (int)$_SESSION['user_id'];

// Build data array only with provided fields (supports partial updates like toggleActive)
$data = [];
$fields = [
    'policy_name', 'priority', 'category_id', 'location_id', 'request_type', 
    'is_event_support', 'response_minutes', 'diagnosis_minutes', 'resolution_minutes', 
    'uses_business_hours', 'is_active'
];

foreach ($fields as $f) {
    if (isset($_POST[$f])) {
        if ($f === 'policy_name') {
            $data[$f] = trim($_POST[$f]);
        } elseif (in_array($f, ['priority', 'request_type'])) {
            $data[$f] = $_POST[$f] ?: null;
        } elseif (in_array($f, ['category_id', 'location_id'])) {
            $data[$f] = $_POST[$f] !== '' ? (int)$_POST[$f] : null;
        } else {
            $data[$f] = (int)$_POST[$f];
        }
    }
}

// Validation
if (!$policy_id) {
    // CREATE: require core fields
    if (empty($data['policy_name'])) {
        echo json_encode(['success' => false, 'message' => 'Policy name is required.']);
        exit;
    }
    if (($data['response_minutes'] ?? 0) < 1 || ($data['diagnosis_minutes'] ?? 0) < 1 || ($data['resolution_minutes'] ?? 0) < 1) {
        echo json_encode(['success' => false, 'message' => 'Response, Diagnosis, and Resolution minutes must be at least 1.']);
        exit;
    }
} else {
    // UPDATE: validate only if fields are being changed
    if (isset($data['policy_name']) && $data['policy_name'] === '') {
        echo json_encode(['success' => false, 'message' => 'Policy name cannot be empty.']);
        exit;
    }
    if (isset($data['response_minutes']) && $data['response_minutes'] < 1) {
        echo json_encode(['success' => false, 'message' => 'Response minutes must be at least 1.']);
        exit;
    }
}

if ($policy_id) {
    // UPDATE
    $result = update_sla_policy($pdo, $policy_id, $data, $justification ?: null, $user_id);
} else {
    // CREATE
    $result = create_sla_policy($pdo, $data, $user_id);
}

echo json_encode($result);
