<?php
require_once '../includes/auth_session.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

// Only Admin or Super Admin can perform this action
if (!isAdmin() && !isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $debug_info = [];
    $return_var = 0;
    
    // --- ENHANCED GIT CONFIGURATION ---
    putenv('HOME=' . __DIR__ . '/..');
    putenv('GIT_TERMINAL_PROMPT=0');
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    exec("git config --global --add safe.directory \"$projectRoot\" 2>&1");

    // 1. Force fresh fetch from origin with pruning
    exec("git fetch origin --prune 2>&1", $fetch_output, $fetch_return);
    $debug_info['fetch_output'] = $fetch_output;
    
    if ($fetch_return !== 0) {
        throw new Exception("Failed to connect to GitHub. Please check your internet connection and ensure git is configured correctly.");
    }

    // 2. Determine the active branch (main or master)
    $current_branch = trim(shell_exec("git rev-parse --abbrev-ref HEAD 2>/dev/null") ?: 'main');
    $debug_info['active_branch'] = $current_branch;

    // 3. Get the current local commit hash
    $local_hash = trim(shell_exec("git rev-parse HEAD"));
    
    // 4. Get the latest remote commit hash directly from origin/[branch]
    $remote_hash = trim(shell_exec("git rev-parse origin/$current_branch"));
    
    $debug_info['local_hash'] = $local_hash;
    $debug_info['remote_hash'] = $remote_hash;

    // 5. Comparison Logic
    $update_available = false;
    $message = "";
    $commits_behind = 0;
    $commit_messages = [];

    if ($local_hash !== $remote_hash) {
        $commits_behind = (int)trim(shell_exec("git rev-list HEAD..origin/$current_branch --count"));
        $changed_files = [];
        exec("git diff --name-only HEAD origin/$current_branch", $changed_files);
        
        if ($commits_behind > 0 || !empty($changed_files)) {
            $update_available = true;
            $message = "New update found! You are $commits_behind commit(s) behind.";
            exec("git log HEAD..origin/$current_branch --oneline -5", $commit_messages);
        } else {
             $commits_ahead = (int)trim(shell_exec("git rev-list origin/$current_branch..HEAD --count"));
             if ($commits_ahead > 0) {
                 $update_available = false;
                 $message = "You are $commits_ahead commit(s) AHEAD of the remote version. No updates needed.";
             } else {
                $update_available = true;
                $message = "Version mismatch detected. An update is recommended to safely sync with the repo.";
             }
        }
    } else {
        $update_available = false;
        $message = "Your software is fully up to date.";
    }

    // 6. Sync results with Session to clear/set update lock
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($update_available) {
        $_SESSION['updates_available'] = true;
        $_SESSION['remote_commit'] = substr($remote_hash, 0, 7);
        $_SESSION['local_commit'] = substr($local_hash, 0, 7);
    } else {
        $_SESSION['updates_available'] = false;
        unset($_SESSION['remote_commit']);
        unset($_SESSION['local_commit']);
    }
    $_SESSION['update_check_done'] = true;

    echo json_encode([
        'success' => true,
        'update_available' => $update_available,
        'message' => $message,
        'commits_behind' => $commits_behind,
        'details' => $commit_messages,
        'debug' => $debug_info
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => isset($debug_info) ? $debug_info : []
    ]);
}
?>
