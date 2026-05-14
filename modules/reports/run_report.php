<?php
// modules/reports/run_report.php
// On-demand trigger for cron_email_report.php — generates the monthly PDF.
// Only accessible to admin / super_admin / it_manager roles.

$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';

header('Content-Type: application/json');

if (!in_array((int)$_SESSION['role_id'], [1, 2, 8])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// ── Resolve the correct CLI PHP binary ──────────────────────────
// PHP_BINARY under Apache/XAMPP is php-cgi.exe which can't run CLI scripts.
// We prefer php.exe in the same directory, then fall back to php-cgi.
$php_dir      = dirname(PHP_BINARY);
$php_cli_path = $php_dir . DIRECTORY_SEPARATOR . 'php.exe';   // Windows
if (!file_exists($php_cli_path)) {
    $php_cli_path = $php_dir . DIRECTORY_SEPARATOR . 'php';   // Linux/Mac
}
if (!file_exists($php_cli_path)) {
    $php_cli_path = PHP_BINARY;  // last resort
}

$script  = __DIR__ . '/cron_email_report.php';
$end     = date('Y-m-d');
$pdf_rel = 'public/reports/monthly_report_' . $end . '.pdf';
$pdf_abs = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pdf_rel);

// ── Run the cron script ─────────────────────────────────────────
$output = [];
$code   = 0;
exec(escapeshellarg($php_cli_path) . ' ' . escapeshellarg($script) . ' 2>&1', $output, $code);
$output_text = implode("\n", $output);

// ── Determine success: PDF file must exist ──────────────────────
$pdf_exists = file_exists($pdf_abs);
$pdf_url    = null;
if ($pdf_exists) {
    $pdf_url = rtrim(BASE_URL, '/') . '/' . ltrim($pdf_rel, '/');
}

$ok = $pdf_exists; // success = file actually on disk, regardless of exit code

echo json_encode([
    'ok'       => $ok,
    'pdf_url'  => $pdf_url,
    'pdf_path' => $pdf_abs,    // absolute server path for display
    'php_used' => $php_cli_path,
    'log'      => $output_text,
    'error'    => $ok ? null : ('PDF not generated. PHP binary: ' . $php_cli_path . "\n\nScript output:\n" . $output_text),
]);
