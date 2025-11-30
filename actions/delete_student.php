<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
$db = new Database();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $db->deleteStudent($id);
}

header("Location: ../students.php");
exit;
?>
