<?php
header('Content-Type: application/json');
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$type = isset($input['type']) ? $input['type'] : '';
$action = isset($input['action']) ? $input['action'] : '';
$ids = isset($input['ids']) ? $input['ids'] : [];

if (empty($ids) || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No IDs provided']);
    exit;
}

$db = new Database();
$success = false;
$message = '';

try {
    if ($action === 'delete') {
        if ($type === 'teacher') {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'admin') {
                 throw new Exception("Permission denied");
            }
            $success = $db->deleteTeachers($ids);
            $message = $success ? 'Teachers deleted successfully' : 'Failed to delete teachers';
        } elseif ($type === 'student') {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'admin') {
                 throw new Exception("Permission denied");
            }
            $success = $db->deleteStudents($ids);
            $message = $success ? 'Students deleted successfully' : 'Failed to delete students';
        } else {
            $message = 'Invalid type';
        }
    } elseif ($action === 'mark_alumni') {
        if ($type === 'student') {
            $success = $db->updateStudentsField($ids, 'student_status', 'Alumni');
            $message = $success ? 'Students marked as Alumni' : 'Failed to update students';
        }
    } elseif ($action === 'mark_active') {
        if ($type === 'student') {
            $success = $db->updateStudentsField($ids, 'student_status', 'Active');
            $message = $success ? 'Students marked as Active' : 'Failed to update students';
        }
    } elseif ($action === 'mark_repeater') {
        if ($type === 'student') {
            $success = $db->updateStudentsField($ids, 'is_repeater', '1');
            $message = $success ? 'Students marked as Repeaters' : 'Failed to update students';
        }
    } elseif ($action === 'unmark_repeater') {
        if ($type === 'student') {
            $success = $db->updateStudentsField($ids, 'is_repeater', '0');
            $message = $success ? 'Students unmarked as Repeaters' : 'Failed to update students';
        }
    } else {
        $message = 'Invalid action';
    }
} catch (Exception $e) {
    $success = false;
    $message = $e->getMessage();
}

echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $message]);
