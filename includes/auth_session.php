<?php
require_once __DIR__ . '/functions.php';

// Access path for redirects
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

// Set session cookie security parameters
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']), // Secure if using HTTPS
    'httponly' => true,                  // Prevent JS access to session cookie
    'samesite' => 'Lax'                  // Protect against some CSRF
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
    $gitVersion = shell_exec("git --version");
    if ($gitVersion) {
        try {
            // 1. Fetch latest state without pruning to save time, unless crucial
            // Using a timeout to prevent hanging if network is down
            // In Windows, timeout is harder, so we trust git's internal timeout or rely on default behavior
            // We adding 2>&1 to suppress output to screen
            shell_exec("git fetch origin main 2>&1");
            
            // 2. Get local and remote hashes
            $localHash = trim(shell_exec("git rev-parse HEAD"));
            $remoteHash = trim(shell_exec("git rev-parse origin/main"));
            
            if ($localHash && $remoteHash && $localHash !== $remoteHash) {
                // Check if we are actually behind
                $commitsBehind = (int)trim(shell_exec("git rev-list HEAD..origin/main --count"));
                
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
