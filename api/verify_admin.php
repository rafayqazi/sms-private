<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$password = trim($_POST['password'] ?? '');
$db = new Database();
$username = trim($_SESSION['username'] ?? '');

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

if ($db->verifyAdmin($username, $password)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
}
?>
