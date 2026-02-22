<?php
require_once __DIR__ . '/../includes/license.php';
$current_mac = License::getMacAddress();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_license'])) {
    $mac = $_POST['mac_address'];
    if (License::activate($mac)) {
        $successMsg = "Software successfully licensed for MAC: " . $mac;
    } else {
        $errorMsg = "Failed to save license data.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Required - AR Software Solution</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border-t-8 border-red-600 animate-fade-in">
        <div class="p-8">
            <div class="mb-6">
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto text-red-600 dark:text-red-400 text-4xl shadow-inner">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2 text-center">License Required</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6 text-center text-sm">This software is bound to specific hardware. To continue, please activate your license by binding your MAC address.</p>
            
            <?php if ($successMsg): ?>
                <div class="bg-green-100 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 p-4 mb-6 rounded shadow-sm text-sm" role="alert">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $successMsg; ?></span>
                    </div>
                    <div class="mt-2 text-center">
                        <a href="../index.php" class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-bold transition-colors">Go to Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="bg-red-100 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 p-4 mb-6 rounded shadow-sm text-sm" role="alert">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $errorMsg; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6 border border-gray-200 dark:border-gray-700">
                <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase mb-2 tracking-wider text-center">Current Machine ID</div>
                <div class="text-lg font-mono text-gray-700 dark:text-gray-200 break-all bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700 uppercase select-all text-center">
                    <?php echo htmlspecialchars($current_mac); ?>
                </div>
            </div>
            
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">MAC Address to Bind</label>
                    <div class="flex gap-2">
                        <input type="text" name="mac_address" required 
                            value="<?php echo $current_mac; ?>"
                            class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg font-mono uppercase focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                    </div>
                </div>
                
                <button type="submit" name="activate_license" 
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg transition-all shadow-lg flex items-center justify-center gap-2 hover:-translate-y-0.5"
                    onclick="return confirm('WARNING: Binding to a MAC address will prevent the software from running on other machines. Continue?')">
                    <i class="fas fa-key"></i> Save & Bind This Machine
                </button>
            </form>

            <div class="mt-8 grid grid-cols-1 gap-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/50">
                    <h3 class="font-bold text-blue-800 dark:text-blue-300 text-xs uppercase mb-2 flex items-center gap-2">
                        <i class="fas fa-headset"></i> Support & Activation
                    </h3>
                    <div class="text-blue-700 dark:text-blue-400 text-sm space-y-1">
                        <p><strong>AR Software Solutions</strong></p>
                        <p class="flex items-center gap-2"><i class="fab fa-whatsapp"></i> +923000358189</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-900/50 px-8 py-4 text-center border-t border-gray-100 dark:border-gray-700">
            <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">
                &copy; <?php echo date('Y'); ?> AR Software Solutions
            </span>
        </div>
    </div>
</body>
</html>
