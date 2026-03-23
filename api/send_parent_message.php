<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['parent_cnic']) || $_SESSION['user_type'] !== 'parent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$message = trim($_POST['message'] ?? '');
$type = $_POST['type'] ?? 'General';

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
    exit;
}

$parentCnic = $_SESSION['parent_cnic'];
$parentName = $_SESSION['parent_name'];

$db = new Database();
$parentCnic = $_SESSION['parent_cnic'];

// Use unified sendMessage method
// Format: senderId, senderType, receiverId, receiverType, message
if ($db->sendMessage($parentCnic, 'parent', 'admin', 'admin', "[$type] $message")) {
    echo json_encode(['success' => true, 'message' => 'Your message has been sent to the administration. We will get back to you soon.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again later.']);
}
