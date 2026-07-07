<?php
require_once __DIR__ . '/functions.php';

// Access path for redirects
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

// Set session cookie security parameters
session_set_cookie_params([
    'lifetime' => 14400, // 4 hours (reduced from 24h)
    'path' => '/',
    'domain' => '',
    'secure' => true,    // Always set Secure flag (works on HTTP too, just ignored)
    'httponly' => true,  // Prevent JS access to session cookie
    'samesite' => 'Strict' // Prevent CSRF-based session attacks
]);

session_start();

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // Every 30 mins
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}


// Enforce 1-day timeout
if (isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > 86400) {
        session_unset();
        session_destroy();
        header("Location: {$base_path}login.php?timeout=1");
        exit();
    }
}

// Pages that are accessible without login
$public_pages = ['login.php', 'developer_bio.php', 'license.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user']) && !in_array($current_page, $public_pages)) {
    header("Location: {$base_path}login.php");
    exit();
}

// Initialize missing session variables for backward compatibility
/* 
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = 'admin';
}
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'Admin';
}
*/
if (!isset($_SESSION['assigned_classes'])) {
    $_SESSION['assigned_classes'] = [];
}


// Auto-check for updates on new session (once per login)
if (!isset($_SESSION['update_check_done']) || $_SESSION['update_check_done'] === false) {
    $_SESSION['updates_available'] = false; // Default to false
    
    // Check if git is available
    putenv('HOME=' . __DIR__ . '/..');
    putenv('GIT_TERMINAL_PROMPT=0');
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    exec("git config --global --add safe.directory \"$projectRoot\" 2>&1");

    $gitVersion = shell_exec("git --version");
    if ($gitVersion) {
        try {
            // 1. Determine active branch
            $currentBranch = trim(shell_exec("git rev-parse --abbrev-ref HEAD 2>/dev/null") ?: 'main');
            
            // 2. Fetch latest state
            shell_exec("git fetch origin $currentBranch 2>&1");
            
            // 3. Get local and remote hashes
            $localHash = trim(shell_exec("git rev-parse HEAD"));
            $remoteHash = trim(shell_exec("git rev-parse origin/$currentBranch"));
            
            if ($localHash && $remoteHash && $localHash !== $remoteHash) {
                // Check if we are actually behind
                $commitsBehind = (int)trim(shell_exec("git rev-list HEAD..origin/$currentBranch --count"));
                
                if ($commitsBehind > 0) {
                    $_SESSION['updates_available'] = true;
                    $_SESSION['remote_commit'] = substr($remoteHash, 0, 7);
                    $_SESSION['local_commit'] = substr($localHash, 0, 7);
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    $_SESSION['update_check_done'] = true;
    $_SESSION['update_notification_dismissed'] = false;
}

// MANDATORY UPDATE SYSTEM: Enforce lock if update is available for more than 1 minute
if (isset($_SESSION['updates_available']) && $_SESSION['updates_available'] === true) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $isApiCall = (strpos($_SERVER['REQUEST_METHOD'], 'POST') !== false || strpos($_SERVER['PHP_SELF'], '/api/') !== false);
    $isUpdatePage = ($currentPage === 'update_required.php');
    $isSettingsUpdate = ($currentPage === 'settings.php' && isset($_GET['tab']) && $_GET['tab'] === 'updates');
    
    // Check grace period (60 seconds)
    if (isset($_SESSION['login_time'])) {
        $secondsSinceLogin = time() - $_SESSION['login_time'];
        
        if ($secondsSinceLogin > 60 && !$isUpdatePage && !$isApiCall && !$isSettingsUpdate) {
            // Redirect to lock page
            $lockPath = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? 'update_required.php' : 'pages/update_required.php';
            header("Location: $lockPath");
            exit();
        }
    }
}
?>
