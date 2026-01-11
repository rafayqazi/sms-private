<?php
// require_once '../includes/auth_session.php'; // Skip auth for CLI debug
require_once __DIR__ . '/../includes/db.php';

$db = new Database();
// We need to bypass the CSV read permissions? No, PHP CLI has access.

$teachers = $db->getAllTeachers();

echo "Running Debug Script...\n";
echo "Found " . count($teachers) . " teachers.\n";

foreach ($teachers as $t) {
    echo "ID: " . $t['id'] . "\n";
    echo "Name: " . $t['name'] . "\n";
    echo "Stored Image Path: '" . $t['profile_image'] . "'\n";
    
    $path = $t['profile_image'];
    
    // Check relative to 'pages/' directory as the web script would
    $pagesDir = __DIR__;
    $fullPath = $pagesDir . '/' . $path;
    
    echo "Checking path relative to pages/: '$fullPath'\n";
    $exists = file_exists($fullPath) ? "YES" : "NO";
    echo "Result: $exists\n";
    
    $altPath = '../' . $path;
    $fullAltPath = $pagesDir . '/' . $altPath;
    echo "Checking alt path (../ + path): '$fullAltPath'\n";
    $existsAlt = file_exists($fullAltPath) ? "YES" : "NO";
    echo "Result: $existsAlt\n";
    
    echo "-------------------\n";
}
