<?php
// api/restore_data.php

// require_once '../includes/auth_session.php'; // Bypassed for installer support
require_once '../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Clean any buffer to ensure pure JSON output
if (ob_get_length()) ob_clean();
ob_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// No password verification as per user request


// Check for file upload
if (!isset($_FILES['backup_file'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
    exit;
}

if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Upload failed: ';
    switch ($_FILES['backup_file']['error']) {
        case UPLOAD_ERR_INI_SIZE:   $errorMsg .= 'File exceeds upload_max_filesize in php.ini'; break;
        case UPLOAD_ERR_FORM_SIZE:  $errorMsg .= 'File exceeds MAX_FILE_SIZE in HTML form'; break;
        case UPLOAD_ERR_PARTIAL:   $errorMsg .= 'File was only partially uploaded'; break;
        case UPLOAD_ERR_NO_FILE:    $errorMsg .= 'No file was uploaded'; break;
        case UPLOAD_ERR_NO_TMP_DIR: $errorMsg .= 'Missing a temporary folder'; break;
        case UPLOAD_ERR_CANT_WRITE: $errorMsg .= 'Failed to write file to disk'; break;
        case UPLOAD_ERR_EXTENSION:  $errorMsg .= 'A PHP extension stopped the file upload'; break;
        default:                   $errorMsg .= 'Unknown upload error'; break;
    }
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

$zipPath = $_FILES['backup_file']['tmp_name'];
$projectRoot = __DIR__ . '/../';

if (!class_exists('ZipArchive')) {
    echo json_encode(['success' => false, 'message' => 'PHP ZipArchive extension is not enabled in this XAMPP installation. Please enable it in php.ini.']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipPath) === TRUE) {
    // Create data and uploads directories if they don't exist
    $dataDir = $projectRoot . 'data';
    $uploadsDir = $projectRoot . 'uploads';
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    
    // Extract the entire backup to project root
    // The ZIP contains data/ and uploads/ folders which will merge with existing ones
    if ($zip->extractTo($projectRoot)) {
        $zip->close();
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Database successfully restored! All data has been updated.']);
    } else {
        $zip->close();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to extract backup file.']);
    }
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Could not open ZIP file. It might be corrupted.']);
}
?>
