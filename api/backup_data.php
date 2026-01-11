<?php
// api/backup_data.php

require_once '../includes/auth_session.php';

require_once '../includes/auth_session.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Method");
}

if (!isset($_POST['password'])) {
    die("Password Required");
}

$password = $_POST['password'];
$verified = false;

// Check if super admin
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    // Hardcoded admin password from login.php
    if ($password === 'admin') {
        $verified = true;
    }
} 
// Check if teacher/admin (dynamically from DB)
else if (isset($_SESSION['username'])) {
    $db = new Database();
    $userRole = $db->getUserRoleByUsername($_SESSION['username']);
    if ($userRole && password_verify($password, $userRole['password_hash'])) {
        $verified = true;
    }
}

if (!$verified) {
    die("Incorrect Password. <a href='javascript:history.back()'>Go Back</a>");
}

$dataDir = __DIR__ . '/../data';
$zipFile = __DIR__ . '/../temp_backup_' . date('Ymd_His') . '.zip';

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Cannot Create Backup File.");
}

// Create recursive directory iterator
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dataDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    // Skip directories (they would be added automatically)
    if (!$file->isDir()) {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(realpath($dataDir)) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

// Download the file
if (file_exists($zipFile)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="school_db_backup_' . date('Y-m-d_H-i-s') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear output buffer
    if(ob_get_length()) ob_clean();
    flush();
    
    readfile($zipFile);
    
    // Delete the file after download
    unlink($zipFile);
    exit;
} else {
    die("Error creating zip file.");
}
?>
