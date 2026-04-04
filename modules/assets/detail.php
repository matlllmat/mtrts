<?php
// Redirects to the new location. This file is kept only for backward compatibility.
$id = (int)($_GET['id'] ?? 0);
header('Location: view.php' . ($id ? "?id={$id}" : ''));
exit;
