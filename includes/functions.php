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
            'messages.php'
        ];
        return in_array($page, $allowedPages);
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
            'assign_roles.php',
            'settings.php'
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
    $db = new Database();
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

function sendRoleChangeEmail($to, $name, $role, $username, $password = null, $action = 'assigned') {
    $subject = "Role Update Notification - GBPS Ali Bux Jarwar";
    
    // Construct message based on action
    $message = "
    <html>
    <head>
        <title>Role Update Notification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: #15803d; color: white; padding: 10px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; }
            .footer { text-align: center; font-size: 0.8em; color: #777; margin-top: 20px; }
            .highlight { color: #15803d; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Role Update Notification</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$name</strong>,</p>";

    if ($action === 'removed') {
        $message .= "
                <p>Your user role has been <span class='highlight' style='color: #dc2626;'>removed</span> from the School Management System.</p>
                <p>You no longer have access to the administrative/editor panel.</p>";
    } else {
        $actionText = ($action === 'updated') ? 'updated' : 'assigned';
        $message .= "
                <p>Your user role has been <span class='highlight'>$actionText</span> in the School Management System.</p>
                <p><strong>Role:</strong> $role</p>
                <p><strong>Username:</strong> $username</p>";
        
        if ($password) {
            $message .= "<p><strong>Password:</strong> $password</p>";
        } else {
            $message .= "<p><strong>Password:</strong> (Unchanged)</p>";
        }
        
        $message .= "
                <p>Please log in to the system to access your assigned features.</p>";
    }

    $message .= "
                <p>Best Regards,<br>GBPS Ali Bux Jarwar Administration</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@school.com' . "\r\n";

    // Send email
    // Note: This requires a configured mail server (e.g., SMTP in php.ini) to work on localhost
    return mail($to, $subject, $message, $headers);
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
