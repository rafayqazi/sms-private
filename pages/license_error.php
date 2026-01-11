<?php
require_once __DIR__ . '/../includes/license.php';
$current_mac = License::getMacAddress();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Required - AR Software Solution</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 text-center border-t-8 border-red-600">
        <div class="mb-6">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto text-red-600 text-4xl">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">License Invalid or Expired</h1>
        <p class="text-gray-600 mb-6">This software is licensed to run on a specific hardware only. It seems your machine is not authorized to run this software.</p>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-8 text-left border border-gray-200">
            <div class="text-xs font-bold text-gray-400 uppercase mb-2">Your Hardware ID (MAC Address)</div>
            <div class="text-lg font-mono text-gray-700 break-all bg-white p-2 rounded border border-gray-100 uppercase select-all">
                <?php echo htmlspecialchars($current_mac); ?>
            </div>
            <p class="text-xs text-gray-500 mt-2 italic">Please provide this ID to the developer to activate your license.</p>
        </div>
        
        <div class="space-y-4">
            <div class="p-4 bg-teal-50 rounded-lg border border-teal-100 text-left">
                <h3 class="font-bold text-teal-800 text-sm mb-1">Contact for Activation:</h3>
                <p class="text-teal-700 text-sm">
                    <strong>Developer:</strong> Abdul Rafay Qazi<br>
                    <strong>Company:</strong> AR Software Solution<br>
                    <strong>WhatsApp:</strong> +923000358189
                </p>
            </div>
            
            <?php 
            session_start();
            if (isset($_SESSION['user']) && $_SESSION['user_type'] === 'admin'): 
            ?>
                <a href="settings.php?tab=licensing" class="block w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg transition-colors shadow-md">
                    <i class="fas fa-tools mr-2"></i> Manage License Settings
                </a>
            <?php else: ?>
                <a href="../login.php" class="block w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 rounded-lg transition-colors">
                    Try Accessing Login
                </a>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> AR Software Solution. All rights reserved.
        </div>
    </div>
</body>
</html>
