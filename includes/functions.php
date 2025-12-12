<?php
function formatCnic($cnic) {
    // Remove any non-numeric characters
    $cnic = preg_replace('/[^0-9]/', '', $cnic);
    
    // Check if we have exactly 13 digits
    if (strlen($cnic) == 13) {
        return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
    }
    
    // Return original if not 13 digits (or maybe partially formatted if you prefer, but requirement is strict)
    return $cnic;
}

function formatContact($contact) {
    // Remove any non-numeric characters
    $contact = preg_replace('/[^0-9]/', '', $contact);
    
    // Check if we have exactly 11 digits (e.g., 03001234567)
    if (strlen($contact) == 11) {
        return substr($contact, 0, 4) . '-' . substr($contact, 4, 7);
    }
    
    return $contact;
}

// Permission Checking Functions
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
}

function isEditor() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Editor';
}

function isSuperAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function canAccessPage($page) {
    // Super admin can access everything
    if (isSuperAdmin()) {
        return true;
    }
    
    // Admin role (teachers with admin role) can access everything
    if (isAdmin()) {
        return true;
    }
    
    // Editor restrictions - can only access attendance pages
    if (isEditor()) {
        $restrictedPages = [
            'student_form.php',
            'students.php',
            'promote_students.php',
            'alumni.php',
            'teacher_form.php',
            'teacher_profile.php',
            'parents.php',
            'reset_app.php',
            'assign_roles.php'
        ];
        
        return !in_array($page, $restrictedPages);
    }
    
    return false;
}

function getAssignedClasses() {
    if (isEditor()) {
        return isset($_SESSION['assigned_classes']) ? $_SESSION['assigned_classes'] : [];
    }
    // Admin/Super Admin can see all classes
    return ['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'];
}

function canEditStudent($studentClass) {
    if (isAdmin() || isSuperAdmin()) {
        return true;
    }
    
    if (isEditor()) {
        $assignedClasses = getAssignedClasses();
        return in_array($studentClass, $assignedClasses);
    }
    
    return false;
}

function getUserDisplayName() {
    if (isSuperAdmin()) {
        return 'Admin';
    } else if (isset($_SESSION['teacher_name'])) {
        return $_SESSION['teacher_name'];
    }
    return 'User';
}

function getUserRoleBadge() {
    $role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Admin';
    $type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';
    
    if ($type === 'admin') {
        return 'Admin';
    } else {
        return $role . ' (Teacher)';
    }
}
?>
