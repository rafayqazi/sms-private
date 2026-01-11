<?php
require_once '../includes/auth_session.php';

header('Content-Type: application/json');

// Only Admin can perform this action
if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $output = [];
    $return_var = 0;

    // Perform git pull
    // 2>&1 to capture error messages
    exec("git pull origin main 2>&1", $output, $return_var);

    // If 'main' fails, try just 'git pull' which uses default tracking
    if ($return_var !== 0) {
         exec("git pull 2>&1", $output, $return_var);
    }

    if ($return_var !== 0) {
        throw new Exception("Update failed: " . implode("\n", $output));
    }

    echo json_encode([
        'success' => true,
        'message' => "Software updated successfully!",
        'details' => $output
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
