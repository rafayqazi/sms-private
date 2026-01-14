<?php
require_once __DIR__ . '/functions.php';

// Set session cookie lifetime to 1 day (86400 seconds)
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

session_start();

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
