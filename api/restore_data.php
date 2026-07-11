<?php
// api/restore_data.php

// Ensure strict JSON output even if errors occur
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/auth_session.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isAdmin() && !isSuperAdmin()) {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_FILES['backup_file'])) {
        throw new Exception('No file was uploaded.');
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
        throw new Exception($errorMsg);
    }

    $zipPath = $_FILES['backup_file']['tmp_name'];
    $projectRoot = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;

    if (!class_exists('ZipArchive')) {
        throw new Exception('PHP ZipArchive extension is not enabled. Please enable it in php.ini.');
    }

    $zip = new ZipArchive();
    $openResult = $zip->open($zipPath);
    if ($openResult !== TRUE) {
        throw new Exception('Could not open ZIP file (error code: ' . $openResult . '). It might be corrupted.');
    }

    // Create data and uploads directories if they don't exist
    $dataDir = $projectRoot . 'data';
    $uploadsDir = $projectRoot . 'uploads';
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    
    if (!$zip->extractTo($projectRoot)) {
        $zip->close();
        throw new Exception('Failed to extract backup file. Check directory permissions.');
    }
    
    $zip->close();
    
    // Clean all output buffers
    while (ob_get_level() > 0) ob_end_clean();
    
    echo json_encode(['success' => true, 'message' => 'Database successfully restored! All data has been updated.']);
    
} catch (Exception $e) {
    // Clean all output buffers
    while (ob_get_level() > 0) ob_end_clean();
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
