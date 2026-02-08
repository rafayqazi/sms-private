<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

$password = $_POST['password'];
$verified = false;
$db = new Database();

// Get current username or 'abdul rafay' if superadmin
$username = $_SESSION['username'] ?? '';

// 1. Robust Password Verification
if ($db->verifyAdmin($username, $password)) {
    $verified = true;
} else if ($username) {
    $userRole = $db->getUserRoleByUsername($username);
    if ($userRole && $userRole['role'] === 'Admin' && password_verify($password, $userRole['password_hash'])) {
        $verified = true;
    }
}

if (!$verified) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

// 2. Automated Backup before Reset
if (!$db->backupData()) {
    echo json_encode(['success' => false, 'message' => 'System reset failed: Could not create backup.']);
    exit;
}

// 3. Perform Reset
if ($db->resetData()) {
    // Log out user for safety
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'System successfully reset. Database and License wiped. You will be logged out.']);
} else {
    echo json_encode(['success' => false, 'message' => 'System reset failed during data wipe.']);
}
