<?php
// Redirects to the new location. This file is kept only for backward compatibility.
$id        = (int)($_GET['id']        ?? 0);
$parent_id = (int)($_GET['parent_id'] ?? 0);
if ($id) {
    header("Location: edit.php?id={$id}");
} elseif ($parent_id) {
    header("Location: add.php?parent_id={$parent_id}");
} else {
    header('Location: add.php');
}
exit;
