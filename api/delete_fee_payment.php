<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['error' => 'Missing Transaction ID']);
    exit;
}

$db = new Database();
$result = $db->deleteFeePayment($id);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to delete record or record not found']);
}
