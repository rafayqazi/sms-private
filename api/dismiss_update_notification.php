<?php
// api/dismiss_update_notification.php
// Dismiss the update notification for current session

session_start();
$_SESSION['update_notification_dismissed'] = true;

echo json_encode(['success' => true]);
