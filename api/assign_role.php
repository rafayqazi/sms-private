<?php
ob_start(); // Start output buffering to catch any stray output
session_start();
require_once '../includes/db.php';

ob_end_clean(); // Clear any output that may have occurred
header('Content-Type: application/json');

// Only allow admins
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['teacherId']) || !isset($input['role']) || !isset($input['username']) || !isset($input['classes'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$db = new Database();
$teacherId = $input['teacherId'];
$role = $input['role'];
$username = $input['username'];
$password = $input['password'] ?? '';
$classes = $input['classes'];
$isEdit = $input['isEdit'] ?? false;

// Validate role
if (!in_array($role, ['Admin', 'Editor'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Validate classes
if (empty($classes) || !is_array($classes)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one class']);
    exit;
}

error_reporting(0); // Suppress warnings to ensure valid JSON
ini_set('display_errors', 0);

// Include functions for email support
require_once '../includes/functions.php';

try {
    // Create or update role
    if ($isEdit) {
        $result = $db->updateUserRole($teacherId, $role, $username, $password, $classes);
        $action = 'updated';
    } else {
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Password is required for new roles']);
            exit;
        }
        $result = $db->createUserRole($teacherId, $role, $username, $password, $classes);
        $action = 'assigned';
    }
    
    // Send email notification if successful
    if ($result['success']) {
        $teacher = $db->getTeacher($teacherId);
        if ($teacher && !empty($teacher['email'])) {
            sendRoleChangeEmail(
                $teacher['email'],
                $teacher['name'],
                $role,
                $username,
                $password ? $password : null, // Only send password if changed/set
                $action
            );
        }
    }
    
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
