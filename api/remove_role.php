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

if (!isset($input['teacherId'])) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

// Include functions for email support
require_once '../includes/functions.php';

try {
    $db = new Database();
    
    // Get teacher info before/after deletion (from teachers.csv so it persists)
    $teacher = $db->getTeacher($input['teacherId']);
    
    $result = $db->deleteUserRole($input['teacherId']);
    
    // Send email notification if successful
    if ($result['success'] && $teacher && !empty($teacher['email'])) {
        sendRoleChangeEmail(
            $teacher['email'],
            $teacher['name'],
            'N/A', // Role removed
            'N/A', // Username removed
            null,
            'removed'
        );
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
