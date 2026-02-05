<?php
require_once __DIR__ . '/functions.php';

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
        header("Location: login.php?timeout=1");
        exit();
    }
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
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
?>
