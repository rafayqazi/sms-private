<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$password = $_POST['password'] ?? '';
$db = new Database();
$username = $_SESSION['username'] ?? '';

if (!$password) {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

$verified = false;

// 1. Check via verifyAdmin (handles superadmin and school-settings admin)
if ($db->verifyAdmin($username, $password)) {
    $verified = true;
} 
// 2. Check Teacher credentials if they have Admin role
else if ($username) {
    $userRole = $db->getUserRoleByUsername($username);
    if ($userRole && $userRole['role'] === 'Admin' && password_verify($password, $userRole['password_hash'])) {
        $verified = true;
    }
}

if ($verified) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
}
?>
