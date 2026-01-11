<?php
require_once '../includes/auth_session.php';

header('Content-Type: application/json');

// Only Admin can perform this action
if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // 1. Fetch latest changes from remote
    // 2>&1 redirects stderr to stdout so we capture errors
    $output = [];
    $return_var = 0;
    
    // Check if git is available
    exec("git --version", $output, $return_var);
    if ($return_var !== 0) {
        throw new Exception("Git is not installed or not found in PATH.");
    }

    // Fetch origin
    exec("git fetch origin 2>&1", $output, $return_var);
    if ($return_var !== 0) {
        throw new Exception("Failed to fetch updates. Check internet connection.");
    }

    // Check status (commits behind)
    // git rev-list HEAD...origin/main --count
    // Assuming 'main' is the branch. flexible approach: use @{u} for upstream
    $local_hash = trim(shell_exec("git rev-parse HEAD"));
    $remote_hash = trim(shell_exec("git rev-parse @{u}"));
    
    if ($local_hash !== $remote_hash) {
        // Count commits behind
        $commits_behind = trim(shell_exec("git rev-list HEAD..@{u} --count"));
        
        if ($commits_behind > 0) {
            echo json_encode([
                'success' => true,
                'update_available' => true,
                'message' => "Update available! You are $commits_behind commit(s) behind.",
                'commits_behind' => $commits_behind
            ]);
        } else {
             echo json_encode([
                'success' => true,
                'update_available' => false,
                'message' => "You are ahead of the remote branch. No updates needed."
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'update_available' => false,
            'message' => "Your software is up to date."
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $output // Optional: remove in production if sensitive
    ]);
}
?>
