<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['action'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$studentId = $data['id'];
$action = $data['action'];

// Validate action
if (!in_array($action, ['pass', 'fail', 'stay'])) {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    $db = new Database();
    $result = $db->promoteStudent($studentId, $action);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Student promoted successfully'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to promote student']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
