<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_ids']) || !is_array($data['student_ids']) || empty($data['student_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No students selected for restoration']);
    exit;
}

if (!isset($data['target_class']) || empty($data['target_class'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a target class']);
    exit;
}

try {
    $db = new Database();
    $result = $db->bulkRestoreStudents($data['student_ids'], $data['target_class']);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => count($data['student_ids']) . ' student(s) restored successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to restore students. Please try again.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
