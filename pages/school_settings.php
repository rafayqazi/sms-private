<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$settingsFile = __DIR__ . '/../data/settings.json';
$successMsg = '';
$errorMsg = '';

// Load current settings
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Verification
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    
    if (isset($_POST['update_settings'])) {
        $settings['school_name'] = $_POST['school_name'];
        $settings['address_tagline'] = $_POST['address_tagline'];
        $settings['headmaster_name'] = $_POST['headmaster_name'];
        $settings['semis_code'] = $_POST['semis_code'];
// Maintenance mode toggle removed from UI, controlled via settings.json only

        if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
            $successMsg = "Settings updated successfully!";
        } else {
            $errorMsg = "Failed to update settings. Check file permissions.";
        }
    }
}
?>

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 p-8 text-white">
            <h2 class="text-3xl font-extrabold flex items-center gap-3">
                <i class="fas fa-cogs"></i> School Settings
            </h2>
            <p class="text-indigo-100 mt-2 opacity-90">Manage school information and system settings.</p>
        </div>

        <div class="p-8">
            <?php if ($successMsg): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-xl flex items-center gap-3 animate-bounce">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span class="font-bold"><?php echo $successMsg; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-xl flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                    <span class="font-bold"><?php echo $errorMsg; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-8">
                <?php echo csrfInput(); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider">School Name</label>
                        <input type="text" name="school_name" value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider">Address Tagline</label>
                        <input type="text" name="address_tagline" value="<?php echo htmlspecialchars($settings['address_tagline'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider">Headmaster Name</label>
                        <input type="text" name="headmaster_name" value="<?php echo htmlspecialchars($settings['headmaster_name'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider">SEMIS Code</label>
                        <input type="text" name="semis_code" value="<?php echo htmlspecialchars($settings['semis_code'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all font-medium">
                    </div>
                </div>

                <hr class="border-gray-100">

                <div class="flex justify-end pt-4">
                    <button type="submit" name="update_settings" class="px-12 py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 flex items-center gap-3 active:scale-95">
                        <i class="fas fa-save"></i> SAVE CHANGES
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
