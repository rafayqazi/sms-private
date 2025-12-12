<?php
require_once __DIR__ . '/functions.php';

session_start();
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
