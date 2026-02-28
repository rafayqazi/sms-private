<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/db.php';
$db = new Database();
$password = trim($_POST['password'] ?? '');
$username = trim($_SESSION['username'] ?? '');

if (!$db->verifyAdmin($username, $password)) {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Invalid password']));
}

// Get school name
$settings = $db->getSchoolSettings();
$schoolName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $settings['school_name'] ?? 'School');
$zipFilename = $schoolName . '_Backup_' . date('Y-m-d_H-i-s') . '.zip';
$tempZip = __DIR__ . '/../data/temp_backup_' . uniqid() . '.zip';

// Create ZIP
$zip = new ZipArchive();
if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Cannot create ZIP file']));
}

// Function to add directory to ZIP
function addDirectoryToZip($zip, $dirPath, $zipPath = '') {
    if (!is_dir($dirPath)) return 0;
    
    // Files to exclude from backup
    $excludedFiles = ['license.php', 'settings.json'];
    
    $count = 0;
    $items = scandir($dirPath);
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        // Skip excluded files
        if (in_array($item, $excludedFiles)) continue;
        
        $fullPath = $dirPath . DIRECTORY_SEPARATOR . $item;
        $zipItemPath = $zipPath ? $zipPath . '/' . $item : $item;
        
        if (is_dir($fullPath)) {
            $zip->addEmptyDir($zipItemPath);
            $count += addDirectoryToZip($zip, $fullPath, $zipItemPath);
        } else {
            if ($zip->addFile($fullPath, $zipItemPath)) {
                $count++;
            }
        }
    }
    
    return $count;
}

// Add data folder
$dataDir = __DIR__ . '/../data';
$dataCount = 0;
if (is_dir($dataDir)) {
    $zip->addEmptyDir('data');
    $dataCount = addDirectoryToZip($zip, $dataDir, 'data');
}

// Add uploads folder
$uploadsDir = __DIR__ . '/../uploads';
$uploadsCount = 0;
if (is_dir($uploadsDir)) {
    $zip->addEmptyDir('uploads');
    $uploadsCount = addDirectoryToZip($zip, $uploadsDir, 'uploads');
}

$zip->close();

// Check if ZIP was created successfully
if (!file_exists($tempZip)) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'ZIP file was not created']));
}

$fileSize = filesize($tempZip);
if ($fileSize == 0) {
    unlink($tempZip);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'ZIP is empty. Files added: data=' . $dataCount . ', uploads=' . $uploadsCount]));
}

// Clear any accidental output
ob_end_clean();

// Send the file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($tempZip);
unlink($tempZip);
exit;
