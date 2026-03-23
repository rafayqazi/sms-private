<?php
// Start session the same way auth_session does
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check auth manually (don't use auth_session.php which redirects)
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

if (!isAdmin() && !isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$recipientId = $_POST['recipient_id'] ?? '';
$recipientType = $_POST['recipient_type'] ?? '';
$status = $_POST['status'] ?? 'Resolved';
$customMessage = trim($_POST['custom_message'] ?? '');

if (empty($recipientId) || empty($recipientType)) {
    echo json_encode(['success' => false, 'message' => 'Missing recipient info.']);
    exit;
}

$db = new Database();

// Build the resolution message
$statusMessages = [
    'Resolved' => '✅ Your ticket has been resolved. Thank you for contacting us. If you have further concerns, feel free to open a new ticket.',
    'Pending' => '⏳ Your ticket is currently under review. We will get back to you shortly. Thank you for your patience.',
    'Rejected' => '❌ Your ticket has been reviewed and closed. If you believe this is an error, please open a new ticket with more details.'
];

if ($status === 'Custom' && !empty($customMessage)) {
    $finalMessage = "📋 $customMessage";
} else {
    $finalMessage = $statusMessages[$status] ?? $statusMessages['Resolved'];
}

// Save the resolution record
$file = __DIR__ . '/../data/resolved_tickets.csv';
$resHeaders = ['id', 'recipient_id', 'recipient_type', 'status', 'message', 'resolved_at', 'resolved_by'];

if (!file_exists($file)) {
    $fp = fopen($file, 'w');
    fputcsv($fp, $resHeaders);
    fclose($fp);
}

$resId = time() . rand(100, 999);
$record = [
    $resId,
    $recipientId,
    $recipientType,
    $status === 'Custom' ? 'Custom' : $status,
    $finalMessage,
    date('Y-m-d H:i:s'),
    $_SESSION['user'] ?? 'admin'
];

$fp = fopen($file, 'a');
fputcsv($fp, $record);
fclose($fp);

// Delete the conversation from messages.csv
$db->deleteConversation('admin', $recipientId);

echo json_encode(['success' => true, 'message' => 'Ticket has been resolved and conversation cleared.']);
