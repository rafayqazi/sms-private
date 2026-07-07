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

function isViewer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Viewer';
}

function isSuperAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isParent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parent';
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

    // Parents cannot access any admin pages (they use parent_portal.php)
    if (isParent()) {
        return $page === 'parent_portal.php';
    }

    // Viewer restrictions - Read only access to specific pages
    if (isViewer()) {
        $allowedPages = [
            'index.php',
            'students.php',
            'student_profile.php',
            'teacher_profile.php',
            'attendance_view.php',
            'view_results.php',
            'book_bank.php',
            'inventory.php',
            'messages.php',
            'expenses.php',
            'expense_categories.php'
        ];
        return in_array($page, $allowedPages);
    }
    
    // Editor restrictions - can only access attendance and result entry pages
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
            'settings.php',
            'backup_restore.php',
            'manage_classes.php',
            'bulk_admission.php',
            'school_settings.php',
            'certificates.php',
            'certificate_character.php',
            'certificate_school_leaving.php',
            'certificate_testimonial.php',
            'certificate_transfer.php',
            'migrate_classes.php',
            'missing_files.php'
        ];
        
        return !in_array($page, $restrictedPages);
    }
    
    return false;
}

function getAssignedClasses() {
    $db = new Database();
    
    if (isEditor() && isset($_SESSION['teacher_id'])) {
        $teacher = $db->getTeacher($_SESSION['teacher_id']);
        if ($teacher && !empty($teacher['assigned_classes'])) {
            $rawClasses = explode(',', $teacher['assigned_classes']);
            $assignedClasses = [];
            foreach ($rawClasses as $c) {
                $clean = trim($c);
                if ($clean !== '') {
                    $assignedClasses[] = $clean;
                }
            }
            return $assignedClasses;
        }
        return [];
    }
    
    // Admin/Super Admin can see all classes
    return $db->getClassNames();
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



// CSRF Protection Functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function performSilentBackup() {
    $dataDir = __DIR__ . '/../data';
    $uploadsDir = __DIR__ . '/../uploads';
    $backupsDir = __DIR__ . '/../backups';
    
    if (!is_dir($backupsDir)) {
        mkdir($backupsDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $zipFilename = "SafetyBackup_$timestamp.zip";
    $zipPath = $backupsDir . '/' . $zipFilename;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false;
    }
    
    // Function to add directory recursively
    $addDir = function($dirPath, $zipPathName) use (&$zip, &$addDir) {
        if (!is_dir($dirPath)) return;
        $items = scandir($dirPath);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $fullPath = $dirPath . DIRECTORY_SEPARATOR . $item;
            $localPath = $zipPathName . '/' . $item;
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($localPath);
                $addDir($fullPath, $localPath);
            } else {
                $zip->addFile($fullPath, $localPath);
            }
        }
    };
    
    if (is_dir($dataDir)) {
        $zip->addEmptyDir('data');
        $addDir($dataDir, 'data');
    }
    
    if (is_dir($uploadsDir)) {
        $zip->addEmptyDir('uploads');
        $addDir($uploadsDir, 'uploads');
    }
    
    $zip->close();
    return file_exists($zipPath) ? $zipFilename : false;
}
?>
