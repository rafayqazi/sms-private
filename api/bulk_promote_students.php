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

if (!isset($data['promotions']) || !is_array($data['promotions'])) {
    echo json_encode(['error' => 'Missing or invalid promotions data']);
    exit;
}

try {
    $db = new Database();
    $result = $db->bulkPromoteStudents($data['promotions']);

    if ($result['saved'] || $result['promoted'] > 0) {
        echo json_encode([
            'success' => true,
            'message' => $result['promoted'] . ' student(s) processed successfully'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to process promotions']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
