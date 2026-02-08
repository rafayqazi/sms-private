<?php
/**
 * Installation Check
 * 
 * Verifies if the system is fully installed.
 * Redirects to install.php if crucial data files are missing.
 */

function isInstalled() {
    $dbFile = __DIR__ . '/../data/database.csv';
    $licenseFile = __DIR__ . '/../data/license.php';
    
    // System is considered installed only if both files exist
    return file_exists($dbFile) && file_exists($licenseFile);
}

// Global Redirect Logic
$currentPage = basename($_SERVER['PHP_SELF']);
$inApiDir = (strpos($_SERVER['PHP_SELF'], '/api/') !== false);
$inPagesDir = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);

$prefix = ($inPagesDir || $inApiDir) ? '../' : '';

// Pages that are allowed even if not installed
$allowedPages = ['install.php', 'installer_actions.php', 'restore_data.php', 'get_machine_info.php'];

if (!isInstalled()) {
    if (!in_array($currentPage, $allowedPages) && !$inApiDir) {
        header("Location: " . $prefix . "install.php");
        exit();
    }
} else {
    // If installed, prevent access to install.php
    if ($currentPage === 'install.php') {
        header("Location: " . $prefix . "index.php");
        exit();
    }
}
