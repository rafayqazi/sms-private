<?php
require_once '../includes/auth_session.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

// Only Admin can perform this action
if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $debug_info = [];
    $return_var = 0;
    
    // Check if git is available
    exec("git --version", $output_version, $return_var);
    if ($return_var !== 0) {
        throw new Exception("Git is not installed or not found in PATH.");
    }

    // 1. Force fresh fetch from origin with pruning to ensure we have the absolute latest state
    // We use --prune to remove any remote-tracking references that no longer exist on the remote
    exec("git fetch origin --prune 2>&1", $fetch_output, $fetch_return);
    $debug_info['fetch_output'] = $fetch_output;
    
    if ($fetch_return !== 0) {
        throw new Exception("Failed to connect to GitHub. Please check your internet connection.");
    }

    // 2. Get the current local commit hash
    $local_hash = trim(shell_exec("git rev-parse HEAD"));
    
    // 3. Get the latest remote commit hash directly from origin/main
    // standardizing on 'main' as the primary branch
    $remote_hash = trim(shell_exec("git rev-parse origin/main"));
    
    $debug_info['local_hash'] = $local_hash;
    $debug_info['remote_hash'] = $remote_hash;

    // 4. Compare hashes to see if they are different
    if ($local_hash !== $remote_hash) {
        // Calculate how many commits behind we are
        $commits_behind = (int)trim(shell_exec("git rev-list HEAD..origin/main --count"));
        
        // Get the list of changed files for better context
        // This ensures we detect changes even if the commit count somehow gets confused (e.g. force push)
        $changed_files = [];
        exec("git diff --name-only HEAD origin/main", $changed_files);
        
        // If we have commits behind OR file content differences
        if ($commits_behind > 0 || !empty($changed_files)) {
            // Get the last few commit messages for the user
            $commit_messages = [];
            exec("git log HEAD..origin/main --oneline -5", $commit_messages);
            
            echo json_encode([
                'success' => true,
                'update_available' => true,
                'message' => "New update found! You are $commits_behind commit(s) behind.",
                'commits_behind' => $commits_behind,
                'details' => $commit_messages,
                'debug' => $debug_info
            ]);
        } else {
             // Hashes are different but no commits behind? Could be ahead or diverged.
             // Check if we are ahead
             $commits_ahead = (int)trim(shell_exec("git rev-list origin/main..HEAD --count"));
             
             if ($commits_ahead > 0) {
                 echo json_encode([
                    'success' => true,
                    'update_available' => false,
                    'message' => "You are $commits_ahead commit(s) AHEAD of the remote version. No updates needed.",
                    'debug' => $debug_info
                ]);
             } else {
                // If we get here, hashes differ but neither ahead nor behind in simple count? 
                // Treat as update available to be safe (sync with remote)
                echo json_encode([
                    'success' => true,
                    'update_available' => true,
                    'message' => "Version mismatch detected. An update is recommended safely sync with the repo.",
                    'commits_behind' => 'Unknown',
                    'debug' => $debug_info
                ]);
             }
        }
    } else {
        echo json_encode([
            'success' => true,
            'update_available' => false,
            'message' => "Your software is fully up to date.",
            'debug' => $debug_info
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => isset($debug_info) ? $debug_info : []
    ]);
}
?>
