<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

// Only Admin can access settings
if ($_SESSION['user_type'] !== 'admin') {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

$db = new Database();
$successMsg = '';
$errorMsg = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general'])) {
        $data = [
            'school_name' => $_POST['school_name'],
            'address_tagline' => $_POST['address_tagline'],
            'semis_code' => $_POST['semis_code'],
            'headmaster_name' => $_POST['headmaster_name']
        ];
        if ($db->updateSchoolSettings($data)) {
            // Handle Logo Upload
            if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
                $allowed = ['png', 'jpg', 'jpeg'];
                $ext = strtolower(pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    // Target: root/GBPS_LOGO.png. Since we are in pages/, we go up one level.
                    $target = '../GBPS_LOGO.png';
                    
                    // Attempt to upload
                    if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $target)) {
                        $successMsg = "Settings and Logo updated successfully!";
                    } else {
                        $successMsg = "Settings updated, but Logo upload failed.";
                    }
                } else {
                     $errorMsg = "Invalid logo format. Only PNG, JPG allowed.";
                }
            } else {
                $successMsg = "School settings updated successfully!";
            }
        } else {
            $errorMsg = "Failed to update settings.";
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];
        
        // Verify current password first
        if ($db->verifyAdmin($_SESSION['username'], $currentPass)) {
            if ($newPass === $confirmPass) {
                if (strlen($newPass) >= 4) {
                    if ($db->updateAdminPassword($newPass)) {
                        $successMsg = "Password changed successfully! Please login again with new password.";
                    } else {
                        $errorMsg = "Failed to update password.";
                    }
                } else {
                    $errorMsg = "New password must be at least 4 characters long.";
                }
            } else {
                $errorMsg = "New passwords do not match.";
            }
        } else {
            $errorMsg = "Current password is incorrect.";
        }
    } elseif (isset($_POST['activate_license'])) {
        $mac = $_POST['mac_address'];
        if (License::activate($mac)) {
            $successMsg = "Software successfully licensed for MAC: " . $mac;
        } else {
            $errorMsg = "Failed to save license data.";
        }
    }
}

$settings = $db->getSchoolSettings();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center gap-3">
            <div class="p-3 bg-teal-100 rounded-lg text-teal-600">
                <i class="fas fa-cog"></i>
            </div>
            Settings
        </h2>

        <?php if ($successMsg): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $successMsg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between" role="alert">
                 <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $errorMsg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="flex border-b">
                <a href="?tab=general" class="flex-1 py-4 text-center font-semibold transition-colors <?php echo $activeTab === 'general' ? 'bg-teal-50 text-teal-700 border-b-2 border-teal-600' : 'text-gray-500 hover:bg-gray-50'; ?>">
                    <i class="fas fa-school mr-2"></i> General Settings
                </a>
                <a href="?tab=security" class="flex-1 py-4 text-center font-semibold transition-colors <?php echo $activeTab === 'security' ? 'bg-teal-50 text-teal-700 border-b-2 border-teal-600' : 'text-gray-500 hover:bg-gray-50'; ?>">
                    <i class="fas fa-lock mr-2"></i> Security
                </a>
                <a href="?tab=licensing" class="flex-1 py-4 text-center font-semibold transition-colors <?php echo $activeTab === 'licensing' ? 'bg-teal-50 text-teal-700 border-b-2 border-teal-600' : 'text-gray-500 hover:bg-gray-50'; ?>">
                    <i class="fas fa-key mr-2"></i> Licensing
                </a>
                <a href="?tab=updates" class="flex-1 py-4 text-center font-semibold transition-colors <?php echo $activeTab === 'updates' ? 'bg-teal-50 text-teal-700 border-b-2 border-teal-600' : 'text-gray-500 hover:bg-gray-50'; ?>">
                    <i class="fas fa-sync-alt mr-2"></i> Software Updates
                </a>
            </div>

            <div class="p-8">
                <?php if ($activeTab === 'general'): ?>
                    <form action="?tab=general" method="POST" class="space-y-6" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">School Name</label>
                                <input type="text" name="school_name" value="<?php echo htmlspecialchars($settings['school_name']); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                <p class="text-xs text-gray-500 mt-1">This name will appear on all reports, attendance sheets, and the login page.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address / Tagline</label>
                                <input type="text" name="address_tagline" value="<?php echo htmlspecialchars($settings['address_tagline']); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SEMIS Code</label>
                                <input type="text" name="semis_code" value="<?php echo htmlspecialchars(isset($settings['semis_code']) ? $settings['semis_code'] : ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Headmaster Name / Signature Text</label>
                                <input type="text" name="headmaster_name" value="<?php echo htmlspecialchars($settings['headmaster_name']); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                <p class="text-xs text-gray-500 mt-1">Text to display at the bottom of reports (e.g. "Headmaster Signature").</p>
                            </div>

                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">School Logo</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 border rounded-lg flex items-center justify-center bg-gray-50 overflow-hidden">
                                        <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="Current Logo" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="school_logo" accept="image/png, image/jpeg"
                                            class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-teal-50 file:text-teal-700
                                            hover:file:bg-teal-100">
                                        <p class="text-xs text-gray-500 mt-1">Upload to replace the current school logo (PNG/JPG). This will update the logo everywhere.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t flex justify-end">
                            <button type="submit" name="save_general" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-8 rounded-lg transition-colors shadow-md flex items-center gap-2">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                <?php elseif ($activeTab === 'security'): ?>
                    <form action="?tab=security" method="POST" class="space-y-6 max-w-lg mx-auto">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-info-circle mr-1"></i> You are changing the password for the admin account <strong><?php echo htmlspecialchars($settings['admin_username']); ?></strong>.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" name="current_password" required placeholder="Enter current password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password" required placeholder="Enter new password" minlength="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required placeholder="Retype new password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <div class="pt-4 border-t flex justify-end">
                            <button type="submit" name="change_password" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg transition-colors shadow-md flex items-center gap-2">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>
                <?php elseif ($activeTab === 'licensing'): ?>
                    <div class="max-w-xl mx-auto space-y-8">
                        <?php 
                        $current_mac = License::getMacAddress(); 
                        $is_licensed = License::isLicensed();
                        $license_file = __DIR__ . '/../data/license.php';
                        $allowed_mac = "Not Set";
                        if (file_exists($license_file)) {
                            $data = include $license_file;
                            $allowed_mac = isset($data['display_mac']) ? $data['display_mac'] : (isset($data['allowed_mac']) ? $data['allowed_mac'] : "Not Set");
                        }
                        ?>

                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                            <div class="p-6 <?php echo $is_licensed ? 'bg-green-50 border-b border-green-100' : 'bg-red-50 border-b border-red-100'; ?>">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-certificate <?php echo $is_licensed ? 'text-green-600' : 'text-red-600'; ?>"></i>
                                        License Status
                                    </h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo $is_licensed ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'; ?>">
                                        <?php echo $is_licensed ? 'Activated' : 'Unlicensed'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500">Current Machine MAC:</span>
                                    <span class="font-mono font-bold text-gray-800 uppercase"><?php echo $current_mac; ?></span>
                                </div>
                            </div>
                        </div>

                        <form action="?tab=licensing" method="POST" class="space-y-6">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-teal-600"></i>
                                    Activate Local License
                                </h3>
                                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                                    To bind this software to this PC, enter the MAC address below. Once saved, the software will <span class="font-bold">only</span> run on this machine or the machine with the matching MAC address.
                                </p>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">MAC Address to Authorize</label>
                                        <div class="flex gap-2">
                                            <input type="text" name="mac_address" required 
                                                value="<?php echo $allowed_mac !== 'Not Set' ? $allowed_mac : $current_mac; ?>"
                                                placeholder="e.g. A0:A8:CD:BF:92:DF"
                                                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg font-mono uppercase focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                            <button type="button" onclick="document.querySelector('input[name=mac_address]').value = '<?php echo $current_mac; ?>'" 
                                                class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm transition-colors">
                                                Use Current
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="pt-4">
                                        <button type="submit" name="activate_license" 
                                            class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2"
                                            onclick="return confirm('WARNING: Binding to a MAC address will prevent the software from running on other machines. Are you sure you want to continue?')">
                                            <i class="fas fa-check-circle"></i> Save & Bind License
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-blue-800 flex items-start gap-2">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <span>Binding to MAC address is a local licensing method. If you change your network card or move the software to another PC, you will need to update this setting or contact the developer.</span>
                            </p>
                        </div>
                    </div>
                <?php elseif ($activeTab === 'updates'): ?>
                    <div class="max-w-3xl mx-auto space-y-8 animate-[fadeIn_0.5s_ease-out]">
                        
                        <!-- Premium Header Card -->
                        <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 text-white text-center shadow-2xl relative overflow-hidden group">
                            <!-- Background Decor -->
                            <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                                <div class="absolute bottom-0 left-0 w-64 h-64 bg-teal-500 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>
                                <i class="fas fa-code text-8xl absolute top-4 left-4 rotate-12 text-white/20"></i>
                                <i class="fas fa-laptop-code text-8xl absolute bottom-4 right-4 -rotate-12 text-white/20"></i>
                            </div>
                            
                            <div class="relative z-10 flex flex-col items-center">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-md rounded-2xl mb-6 shadow-inner ring-1 ring-white/20 group-hover:scale-110 transition-transform duration-500">
                                    <i class="fas fa-sync-alt text-3xl text-indigo-300 animate-[spin_8s_linear_infinite]"></i>
                                </div>
                                <h2 class="text-3xl md:text-4xl font-black tracking-tight mb-2 bg-clip-text text-transparent bg-gradient-to-r from-indigo-200 via-white to-teal-200">
                                    SCHOOL MANAGEMENT SYSTEM
                                </h2>
                                <p class="text-slate-400 font-medium text-lg tracking-wide uppercase text-xs">Powered By AR Software Solution</p>
                                
                                <div class="mt-8 flex justify-center gap-3">
                                    <div class="px-4 py-1.5 bg-slate-800/50 backdrop-blur rounded-full text-xs font-mono border border-slate-700 flex items-center gap-2 text-slate-300">
                                        <i class="fas fa-code-branch"></i> v1.0.0
                                    </div>
                                    <div class="px-4 py-1.5 bg-emerald-500/10 backdrop-blur text-emerald-300 rounded-full text-xs font-bold border border-emerald-500/20 flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        System Stable
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Action Area -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm relative overflow-hidden">
                            <div class="text-center space-y-8">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 flex items-center justify-center gap-2">
                                        <i class="fas fa-cloud-download-alt text-indigo-600"></i> Update Center
                                    </h3>
                                    <p class="text-gray-500 max-w-lg mx-auto mt-2 text-sm leading-relaxed">
                                        Check for the latest security patches, performance optimizations, and new features directly from the official repository.
                                    </p>
                                </div>

                                <!-- Dynamic Status Area -->
                                <div id="update-status-area" class="hidden">
                                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200 inline-block min-w-[320px] shadow-inner">
                                        <div class="flex flex-col items-center gap-3">
                                            <span id="update-icon" class="text-3xl"></span>
                                            <span id="update-message" class="font-bold text-slate-700 text-lg"></span>
                                            <p id="update-details" class="text-xs text-slate-400 font-mono"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-center flex-col sm:flex-row gap-4 pt-2">
                                    <button type="button" onclick="checkUpdates()" id="btn-check-updates" class="group px-8 py-3.5 bg-white text-slate-700 border border-slate-300 font-bold rounded-xl hover:border-indigo-600 hover:text-indigo-600 hover:shadow-md transition-all flex items-center justify-center gap-3">
                                        <i class="fas fa-satellite-dish group-hover:rotate-12 transition-transform"></i> Check for Updates
                                    </button>
                                    
                                    <button type="button" onclick="confirmUpdate()" id="btn-perform-update" class="hidden px-8 py-3.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-3 animate-pulse">
                                        <i class="fas fa-download"></i> Install Update Now
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Developer Footer -->
                        <div class="bg-slate-50 rounded-xl p-5 border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-600 shadow-sm border border-slate-200">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-700 text-sm">AR Software Solutions</h4>
                                    <p class="text-slate-400 text-xs">Excellence in Code & Design</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <a href="https://github.com/rafayqazi" target="_blank" class="text-slate-400 hover:text-slate-800 transition-colors" title="GitHub"><i class="fab fa-github text-lg"></i></a>
                                <a href="https://www.linkedin.com/in/abdulrafayqazi" target="_blank" class="text-slate-400 hover:text-blue-600 transition-colors" title="LinkedIn"><i class="fab fa-linkedin text-lg"></i></a>
                                <a href="https://wa.me/923000358189" target="_blank" class="text-slate-400 hover:text-green-600 transition-colors" title="WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>
                                <a href="mailto:abdulrafehqazi@gmail.com" class="text-slate-400 hover:text-red-500 transition-colors" title="Email"><i class="fas fa-envelope text-lg"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Modal -->
                    <div id="updateModal" class="fixed inset-0 bg-slate-900/80 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden scale-95 opacity-0 transition-all duration-300 transform" id="updateModalContent">
                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-6 border-b border-orange-100 flex items-center gap-4">
                                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-orange-500 shadow-sm flex-shrink-0">
                                    <i class="fas fa-shield-alt text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-800">System Update</h3>
                                    <p class="text-orange-600 text-xs font-bold uppercase tracking-wider">Attention Required</p>
                                </div>
                            </div>
                            
                            <div class="p-6 space-y-4">
                                <p class="text-slate-600 text-sm leading-relaxed font-medium">
                                    You are about to pull the latest updates from the remote repository.
                                </p>
                                <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 flex gap-3">
                                    <i class="fas fa-exclamation-circle text-orange-500 mt-0.5"></i>
                                    <div class="text-xs text-orange-800">
                                        <span class="font-bold block mb-1">Before you proceed:</span>
                                        <ul class="list-disc pl-4 space-y-1 opacity-80">
                                            <li>Backup your database and local files.</li>
                                            <li>Ensure stable internet connection.</li>
                                            <li>Any local code modifications will be overwritten.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-slate-50 flex justify-end gap-3 border-t border-slate-100">
                                <button onclick="closeUpdateModal()" class="px-5 py-2.5 text-slate-500 hover:bg-slate-200 hover:text-slate-700 rounded-lg font-bold text-sm transition-colors">Cancel</button>
                                <button onclick="executeUpdate()" id="btn-execute-update" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-bold text-sm transition-all shadow-lg flex items-center gap-2">
                                    <i class="fas fa-bolt text-amber-400"></i> Update System
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                    function checkUpdates() {
                        const btn = document.getElementById('btn-check-updates');
                        const originalText = btn.innerHTML;
                        const statusArea = document.getElementById('update-status-area');
                        const icon = document.getElementById('update-icon');
                        const msg = document.getElementById('update-message');
                        const details = document.getElementById('update-details');
                        const updateBtn = document.getElementById('btn-perform-update');
                        
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking Remote...';
                        statusArea.classList.add('hidden');
                        updateBtn.classList.add('hidden');

                        fetch('../api/check_update.php')
                            .then(response => response.json())
                            .then(data => {
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                                statusArea.classList.remove('hidden');
                                
                                if (data.update_available) {
                                    icon.className = 'fas fa-gift text-emerald-500 animate-bounce';
                                    msg.className = 'font-bold text-emerald-700 text-lg';
                                    msg.textContent = 'New Update Available!';
                                    details.textContent = data.message;
                                    updateBtn.classList.remove('hidden');
                                } else {
                                    icon.className = 'fas fa-check-circle text-sky-500';
                                    msg.className = 'font-bold text-slate-700 text-lg';
                                    msg.textContent = 'System is Up to Date';
                                    details.textContent = 'You are running the latest version.';
                                }
                            })
                            .catch(err => {
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                                statusArea.classList.remove('hidden');
                                icon.className = 'fas fa-wifi text-red-400';
                                msg.className = 'font-bold text-red-600';
                                msg.textContent = 'Connection Failed';
                                details.textContent = 'Could not contact update server.';
                                console.error(err);
                            });
                    }

                    function confirmUpdate() {
                        const modal = document.getElementById('updateModal');
                        const content = document.getElementById('updateModalContent');
                        modal.classList.remove('hidden');
                        // Small delay to allow display:block to apply before opacity transition
                        setTimeout(() => {
                            content.classList.remove('scale-95', 'opacity-0');
                            content.classList.add('scale-100', 'opacity-100');
                        }, 10);
                    }

                    function closeUpdateModal() {
                        const modal = document.getElementById('updateModal');
                        const content = document.getElementById('updateModalContent');
                        content.classList.remove('scale-100', 'opacity-100');
                        content.classList.add('scale-95', 'opacity-0');
                        setTimeout(() => {
                            modal.classList.add('hidden');
                        }, 300);
                    }

                    function executeUpdate() {
                        const btn = document.getElementById('btn-execute-update');
                        const originalText = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-cog fa-spin"></i> Pulling Changes...';

                        fetch('../api/perform_update.php', { method: 'POST' })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    btn.className = "px-5 py-2.5 bg-emerald-600 text-white rounded-lg font-bold text-sm shadow-lg flex items-center gap-2";
                                    btn.innerHTML = '<i class="fas fa-check"></i> Done! Reloading...';
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    btn.className = "px-5 py-2.5 bg-red-600 text-white rounded-lg font-bold text-sm shadow-lg flex items-center gap-2";
                                    btn.innerHTML = '<i class="fas fa-times"></i> Failed';
                                    alert('Update Error: ' + data.message);
                                    btn.disabled = false;
                                    btn.innerHTML = originalText; 
                                }
                            })
                            .catch(err => {
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                                alert('Network Error during update.');
                            });
                    }
                    </script>
                <?php endif; ?>
            </div>

<?php include '../includes/footer.php'; ?>
