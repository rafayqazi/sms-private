<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$cnic = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($cnic) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both CNIC and Password.']);
    exit;
}

$db = new Database();
$parentData = $db->verifyParentLogin($cnic, $password);

if ($parentData) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['parent_cnic'] = $parentData['father_cnic'];
    $_SESSION['parent_name'] = $parentData['father_name'];
    $_SESSION['user_type'] = 'parent';
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Redirecting...',
        'redirect' => 'pages/parent_portal.php'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid CNIC or Password. Please ensure the password is your eldest child\'s Date of Birth (YYYY-MM-DD).']);
}
