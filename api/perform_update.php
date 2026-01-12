<?php
require_once '../includes/auth_session.php';

header('Content-Type: application/json');
set_time_limit(300); // Allow up to 5 minutes for slow internet

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
    $debug = [];
    $return_var = 0;

    // 1. Reset any local changes to ensure clean pull (Safety step)
    // Note: This discards local unauthorized changes. A production system might want to 'stash' instead.
    // For this use case, we prioritize successful update over preserving local hacks unless ignored.
    exec("git reset --hard HEAD 2>&1", $reset_output, $reset_var);
    $debug['reset'] = $reset_output;

    // 2. Pull the changes
    // Using --rebase can sometimes be cleaner if there are local commits, but standard pull is safer for general use
    // We explicitly specify origin main to be sure
    exec("git pull origin main 2>&1", $pull_output, $return_var);
    $debug['pull'] = $pull_output;

    if ($return_var !== 0) {
        // Fallback: Try a fetch and reset hard to origin/main (More aggressive "Force Update")
        exec("git fetch origin main 2>&1", $fetch_output);
        exec("git reset --hard origin/main 2>&1", $hard_reset_output, $hard_reset_return);
        
        $debug['fallback_fetch'] = $fetch_output;
        $debug['fallback_reset'] = $hard_reset_output;
        
        if ($hard_reset_return !== 0) {
             throw new Exception("Update failed even after forced reset. Git output: " . implode("\n", $pull_output));
        }
    }

    // 3. Post-update tasks (optional)
    // could include: composer install, clearing cache, updating database schema, etc.

    echo json_encode([
        'success' => true,
        'message' => "Software updated successfully to the latest version!",
        'details' => array_merge($output, $debug)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $debug
    ]);
}
?>
