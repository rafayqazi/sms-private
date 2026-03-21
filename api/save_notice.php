<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$title = $_POST['title'] ?? '';
$message = $_POST['message'] ?? '';
$target_cnic = $_POST['target_cnic'] ?? '';
$type = $_POST['type'] ?? 'General';
$expiry_date = $_POST['expiry_date'] ?? '';

if (empty($title) || empty($message) || empty($target_cnic)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$db = new Database();
$data = [
    'id' => time() . rand(100, 999),
    'target_cnic' => $target_cnic,
    'title' => $title,
    'message' => $message,
    'type' => $type,
    'created_at' => date('Y-m-d H:i:s'),
    'expiry_date' => $expiry_date
];

$success = $db->saveParentNotice($data);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save notice']);
}
