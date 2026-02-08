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

if (!isset($_SESSION['user'])) {
    header("Location: {$base_path}login.php");
    exit();
}

// Initialize missing session variables for backward compatibility
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = 'admin';
}
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'Admin';
}
if (!isset($_SESSION['assigned_classes'])) {
    $_SESSION['assigned_classes'] = [];
}


// Auto-check for updates on new session (once per login)
if (!isset($_SESSION['update_check_done']) || $_SESSION['update_check_done'] === false) {
    // Perform update check
    $owner = 'rafayqazi';
    $repo = 'SMS-GBPS-ALI-BUX-JARWAR';
    $branch = 'main';
    
    try {
        $apiUrl = "https://api.github.com/repos/$owner/$repo/commits/$branch";
        
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP',
                    'Accept: application/vnd.github.v3+json'
                ],
                'timeout' => 3 // Quick timeout to avoid blocking
            ]
        ];
        
        $context = stream_context_create($opts);
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if (isset($data['sha'])) {
                $remoteCommit = $data['sha'];
                
                // Get local commit hash
                $localCommit = null;
                $gitRefPath = __DIR__ . '/../.git/refs/heads/' . $branch;
                if (file_exists($gitRefPath)) {
                    $localCommit = trim(file_get_contents($gitRefPath));
                }
                
                // Compare commits
                $_SESSION['updates_available'] = ($localCommit !== $remoteCommit && $localCommit !== null);
                $_SESSION['remote_commit'] = substr($remoteCommit, 0, 7);
                $_SESSION['local_commit'] = $localCommit ? substr($localCommit, 0, 7) : 'unknown';
            } else {
                $_SESSION['updates_available'] = false;
            }
        } else {
            $_SESSION['updates_available'] = false;
        }
    } catch (Exception $e) {
        $_SESSION['updates_available'] = false;
    }
    
    $_SESSION['update_check_done'] = true;
    $_SESSION['update_notification_dismissed'] = false;
}
?>
