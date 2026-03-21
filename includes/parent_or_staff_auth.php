<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

// Access path for redirects
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_staff = isset($_SESSION['user']);
$is_parent = isset($_SESSION['parent_cnic']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parent';

if (!$is_staff && !$is_parent) {
    header("Location: {$base_path}login.php");
    exit();
}

// If it's a parent, we must check if they own the student requested in GET['id']
if ($is_parent && isset($_GET['id'])) {
    $db = new Database();
    $parentCnic = $_SESSION['parent_cnic'];
    $children = $db->getParentChildrenByCnic($parentCnic);
    
    $owns_student = false;
    foreach ($children as $child) {
        if ($child['id'] == $_GET['id']) {
            $owns_student = true;
            break;
        }
    }
    
    if (!$owns_student) {
        die("Unauthorized access to this student's records.");
    }
}
// Staff (Admin/Teacher) have full access to view certificates
