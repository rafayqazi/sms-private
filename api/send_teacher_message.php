<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required.']);
    exit;
}

$db = new Database();
$success = $db->sendMessage($_SESSION['teacher_id'], 'teacher', 'admin', 'admin', $message);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Complaint successfully forwarded to Administration.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send complaint. Please try again.']);
}
