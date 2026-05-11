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

    // 5. Compare hashes to see if they are different
    if ($local_hash !== $remote_hash) {
        // Calculate how many commits behind we are
        $commits_behind = (int)trim(shell_exec("git rev-list HEAD..origin/$current_branch --count"));
        
        // Get the list of changed files
        $changed_files = [];
        exec("git diff --name-only HEAD origin/$current_branch", $changed_files);
        
        // If we have commits behind OR file content differences
        if ($commits_behind > 0 || !empty($changed_files)) {
            // Get the last few commit messages
            $commit_messages = [];
            exec("git log HEAD..origin/$current_branch --oneline -5", $commit_messages);
            
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
             $commits_ahead = (int)trim(shell_exec("git rev-list origin/$current_branch..HEAD --count"));
             
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
