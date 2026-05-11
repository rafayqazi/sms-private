<?php
ob_start();
session_start();
require_once '../includes/db.php';

ob_end_clean();
header('Content-Type: application/json');

// Only allow admins
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password required']);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

// Verify admin password
if ($input['password'] === 'admin') {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
}
