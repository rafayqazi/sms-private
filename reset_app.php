<?php
require_once 'includes/auth_session.php';
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    
    // Hardcoded check matching login.php
    if ($password === 'admin') {
        $db = new Database();
        
        // 1. Backup
        if ($db->backupData()) {
            // 2. Reset
            if ($db->resetData()) {
                // Redirect to login with success message
                session_destroy();
                header("Location: login.php?reset=success");
                exit;
            } else {
                $error = "Backup successful, but reset failed.";
            }
        } else {
            $error = "Backup failed. Reset aborted to protect data.";
        }
    } else {
        $error = "Incorrect Admin Password.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-red-600 p-4 text-white text-center">
            <h2 class="text-xl font-bold"><i class="fas fa-exclamation-triangle"></i> DANGER ZONE</h2>
            <p class="text-sm opacity-90">Factory Reset Application</p>
        </div>
        
        <div class="p-6">
            <div class="mb-6 text-center text-gray-600">
                <p class="mb-2">This action will <strong>PERMANENTLY DELETE</strong> all:</p>
                <ul class="list-disc list-inside text-left inline-block mb-4 text-sm">
                    <li>Student Records</li>
                    <li>Teacher Records</li>
                    <li>Attendance Data</li>
                    <li>Uploaded Images</li>
                </ul>
                <p class="text-sm bg-blue-50 text-blue-700 p-3 rounded border border-blue-200">
                    <i class="fas fa-shield-alt"></i> A backup will be automatically created before deletion.
                </p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" onsubmit="return confirm('FINAL WARNING: Are you absolutely sure you want to wipe all data?');">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Confirm Admin Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" name="password" required placeholder="Enter 'admin' password">
                </div>
                
                <div class="flex items-center justify-between">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800 text-sm font-bold">
                        Cancel
                    </a>
                    <button class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300" type="submit">
                        <i class="fas fa-trash-alt mr-2"></i> Reset Everything
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
