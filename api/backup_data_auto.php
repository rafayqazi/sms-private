<?php
session_start();

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_role'] !== 'Admin')) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once __DIR__ . '/../includes/db.php';
$db = new Database();

$settings = $db->getSchoolSettings();
$schoolName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $settings['school_name'] ?? 'School');
$zipFilename = $schoolName . '_AutoBackup_' . date('Y-m-d_H-i-s') . '.zip';
$tempZip = __DIR__ . '/../temp_autobackup_' . uniqid() . '.zip';

$zip = new ZipArchive();
if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Cannot create ZIP']));
}

function addDirectoryToZip($zip, $dirPath, $zipPath = '') {
    if (!is_dir($dirPath)) return 0;
    
    $count = 0;
    $items = scandir($dirPath);
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
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

$dataDir = __DIR__ . '/../data';
$dataCount = 0;
if (is_dir($dataDir)) {
    $zip->addEmptyDir('data');
    $dataCount = addDirectoryToZip($zip, $dataDir, 'data');
}

$uploadsDir = __DIR__ . '/../uploads';
$uploadsCount = 0;
if (is_dir($uploadsDir)) {
    $zip->addEmptyDir('uploads');
    $uploadsCount = addDirectoryToZip($zip, $uploadsDir, 'uploads');
}

$zip->close();

if (!file_exists($tempZip) || filesize($tempZip) == 0) {
    if (file_exists($tempZip)) unlink($tempZip);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'ZIP empty. Files: data=' . $dataCount . ', uploads=' . $uploadsCount]));
}

$fileSize = filesize($tempZip);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($tempZip);
unlink($tempZip);
exit;
