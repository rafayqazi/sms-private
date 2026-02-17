<?php
// Start output buffering immediately to catch any warnings/notices or accidental whitespace
ob_start();

require_once '../includes/auth_session.php';

header('Content-Type: application/json');
set_time_limit(300); // Allow up to 5 minutes for slow internet

$debug = [];
$output = [];

try {
    // Only Admin can perform this action
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // RELEASE SESSION LOCK: Critical for avoiding Network Errors during long-running tasks
    session_write_close();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 0. Perform Mandatory Safety Backup
    $backupFile = performSilentBackup();
    if (!$backupFile) {
        throw new Exception("Safety backup failed. Update aborted for data security.");
    }
    $debug['backup'] = "Safety backup created: $backupFile";

    // 1. Reset any local changes to ensure clean pull (Safety step)
    // Note: This discards local unauthorized changes. A production system might want to 'stash' instead.
    // For this use case, we prioritize successful update over preserving local hacks unless ignored.
    $reset_output = [];
    $reset_var = 0;
    exec("git reset --hard HEAD 2>&1", $reset_output, $reset_var);
    $debug['reset'] = $reset_output;

    // 2. Pull the changes
    // Using --rebase can sometimes be cleaner if there are local commits, but standard pull is safer for general use
    // We explicitly specify origin main to be sure
    $pull_output = [];
    $return_var = 0;
    exec("git pull origin main 2>&1", $pull_output, $return_var);
    $debug['pull'] = $pull_output;

    if ($return_var !== 0) {
        // Fallback: Try a fetch and reset hard to origin/main (More aggressive "Force Update")
        $fetch_output = [];
        exec("git fetch origin main 2>&1", $fetch_output);
        
        $hard_reset_output = [];
        $hard_reset_return = 0;
        exec("git reset --hard origin/main 2>&1", $hard_reset_output, $hard_reset_return);
        
        $debug['fallback_fetch'] = $fetch_output;
        $debug['fallback_reset'] = $hard_reset_output;
        
        if ($hard_reset_return !== 0) {
             throw new Exception("Update failed even after forced reset. Git output: " . implode("\n", $pull_output));
        }
    }

    // 3. Post-update tasks
    // Re-open session to clear update flags upon success
    session_start();
    unset($_SESSION['updates_available']);
    unset($_SESSION['remote_commit']);
    unset($_SESSION['local_commit']);
    session_write_close();

    // Clean any prior output (warnings, whitespace from includes) so we send clean JSON
    ob_clean();

    echo json_encode([
        'success' => true,
        'message' => "Software updated successfully to the latest version!",
        'backup_file' => $backupFile, // Return backup filename for download
        'details' => $debug
    ]);

} catch (Exception $e) {
    // Detailed Exception Logging for admin
    $debug['error_trace'] = $e->getTraceAsString();
    
    // Clean any prior output to ensure JSON validity
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $debug
    ]);
}

// Flush the buffer and send content
ob_end_flush();
?>
