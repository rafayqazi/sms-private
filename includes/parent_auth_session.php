<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Parent Portal Session Guard
 * This file should be included in parent portal pages.
 * It ensures the user is logged in as a parent.
 */

if (!isset($_SESSION['parent_cnic']) || $_SESSION['user_type'] !== 'parent') {
    // Not logged in as a parent, redirect to login
    header("Location: ../login.php?error=unauthorized_parent");
    exit;
}

// Function to get current parent CNIC
function getLoggedInParentCnic() {
    return $_SESSION['parent_cnic'] ?? null;
}

// Function to get current parent Name
function getLoggedInParentName() {
    return $_SESSION['parent_name'] ?? 'Parent';
}
