<?php
define('DB_HOST', '127.0.0.1:3307'); // CHANGED: original value is 'localhost' — if this doesn't connect, revert to 'localhost'
define('DB_NAME', 'mtrts_sql');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL — adjust if deployed under a sub-folder
define('BASE_URL', '/mtrts/');

$dsn = "mysql:host=" . DB_HOST .
  ";dbname=" . DB_NAME .
  ";charset=" . DB_CHARSET;

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
  // We need to create a designed error for this like the @moduless/denied.php
  die("Database connection failed. Please contact the administrator.");
}
?>