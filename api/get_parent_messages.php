<?php
require_once '../includes/db.php';
require_once '../includes/parent_auth_session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['parent_cnic']) || $_SESSION['user_type'] !== 'parent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$db = new Database();
$parentCnic = $_SESSION['parent_cnic'];

// Get active messages between parent and admin
$messages = $db->getConversation($parentCnic, 'admin');

// Mark as read for parent
$db->markMessagesAsRead($parentCnic, 'admin');

// Get resolved tickets for this parent
$resolvedTickets = [];
$resolvedFile = __DIR__ . '/../data/resolved_tickets.csv';
if (file_exists($resolvedFile)) {
    $handle = fopen($resolvedFile, 'r');
    $headers = fgetcsv($handle, 0, ',');
    while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
        if (count($row) >= 7 && $row[1] === $parentCnic) {
            $resolvedTickets[] = [
                'id' => $row[0],
                'status' => $row[3],
                'message' => $row[4],
                'resolved_at' => $row[5]
            ];
        }
    }
    fclose($handle);
    // Sort by resolved_at descending
    usort($resolvedTickets, function($a, $b) {
        return strtotime($b['resolved_at']) - strtotime($a['resolved_at']);
    });
}

$hasActiveTicket = !empty($messages);

echo json_encode([
    'success' => true,
    'data' => $messages,
    'resolved_tickets' => $resolvedTickets,
    'has_active_ticket' => $hasActiveTicket
]);
