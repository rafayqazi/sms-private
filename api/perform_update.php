<?php
// Start output buffering immediately to catch any warnings/notices or accidental whitespace
ob_start();

require_once '../includes/auth_session.php';

header('Content-Type: application/json');
set_time_limit(300); // Allow up to 5 minutes for slow internet

$debug = [];
$output = [];

try {
    // Only Admin or Super Admin can perform updates
    if (!isAdmin() && !isSuperAdmin()) {
        throw new Exception('Unauthorized access. Only administrators can perform updates.');
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

    // --- ENHANCED GIT CONFIGURATION FOR WINDOWS/XAMPP ---
    // Set HOME environment variable as git often needs it for config/credentials
    putenv('HOME=' . __DIR__ . '/..');
    putenv('GIT_TERMINAL_PROMPT=0'); // Prevent hanging on credential prompts
    
    // Get the absolute path to the project root for safe.directory
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    // 0.5 Ensure git considers this directory safe (Required in newer Git versions)
    // We use "*" to handle cases where the directory name might vary or be accessed via aliases
    exec("git config --global --add safe.directory \"*\" 2>&1", $safe_output);
    $debug['safe_dir'] = $safe_output;

    // 1. Reset any local changes to ensure clean pull (Safety step)
    // We also remove index.lock if it exists to prevent "Another git process seems to be running" error
    $lockFile = __DIR__ . '/../.git/index.lock';
    if (file_exists($lockFile)) {
        unlink($lockFile);
        $debug['lock_removed'] = true;
    }

    $reset_output = [];
    $reset_var = 0;
    exec("git reset --hard HEAD 2>&1", $reset_output, $reset_var);
    $debug['reset'] = $reset_output;

    // 2. Determine the active branch (main or master)
    $current_branch = trim(shell_exec("git rev-parse --abbrev-ref HEAD 2>/dev/null") ?: 'main');
    $debug['active_branch'] = $current_branch;

    // 3. Pull the changes
    $pull_output = [];
    $return_var = 0;
    // We try to pull with --no-edit to avoid entering an editor
    exec("git pull origin $current_branch --no-edit 2>&1", $pull_output, $return_var);
    $debug['pull'] = $pull_output;

    if ($return_var !== 0) {
        // Fallback: Try a fetch and reset hard to origin/[branch] (More aggressive "Force Update")
        $fetch_output = [];
        exec("git fetch origin $current_branch 2>&1", $fetch_output);
        
        $hard_reset_output = [];
        $hard_reset_return = 0;
        exec("git reset --hard origin/$current_branch 2>&1", $hard_reset_output, $hard_reset_return);
        
        $debug['fallback_fetch'] = $fetch_output;
        $debug['fallback_reset'] = $hard_reset_output;
        
        if ($hard_reset_return !== 0) {
             $git_error = implode("\n", array_merge($pull_output, $hard_reset_output));
             throw new Exception("Update failed even after forced reset.\nGit Output:\n" . $git_error);
        }
    }

    // 4. Post-update tasks
    // Re-open session to clear update flags upon success
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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
