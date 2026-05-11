<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check permissions before including header to allow clean header() redirection
if (!canAccessPage('settings.php')) {
    header("Location: ../index.php");
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
            'school_address' => $_POST['school_address'],
            'school_contact' => $_POST['school_contact'],
            'semis_code' => $_POST['semis_code'],
            'headmaster_name' => $_POST['headmaster_name']
        ];
        if ($db->updateSchoolSettings($data)) {
            // Handle Logo Upload
            if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
                $allowed = ['png', 'jpg', 'jpeg'];
                $ext = strtolower(pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    // Create unique filename to avoid browser caching issues
                    $new_filename = 'school_logo_' . time() . '.' . $ext;
                    $target_rel = 'uploads/' . $new_filename;
                    $target_abs = '../' . $target_rel;
                    
                    if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $target_abs)) {
                        // Update the logo path in the database settings
                        $db->updateSchoolSettings(['school_logo' => $target_rel]);
                        $_SESSION['success_message'] = "Settings and Logo updated successfully!";
                    } else {
                        $_SESSION['success_message'] = "Settings updated, but Logo upload failed.";
                    }
                } else {
                     $errorMsg = "Invalid logo format. Only PNG, JPG allowed.";
                }
            } else {
                $_SESSION['success_message'] = "School settings updated successfully!";
            }
            
            // Redirect to index after successful update as requested
            header("Location: ../index.php");
            exit;

        } else {
            $errorMsg = "Failed to update settings.";
        }
    } elseif (isset($_POST['activate_license'])) {
        $mac = $_POST['mac_address'];
        if (License::activate($mac)) {
            $successMsg = "Software successfully licensed for MAC: " . $mac;
        } else {
            $errorMsg = "Failed to save license data.";
        }
    } elseif (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $profileImage = '';

        // Handle Profile Image Upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['png', 'jpg', 'jpeg'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_filename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target_rel = 'uploads/profiles/' . $new_filename;
                $target_abs = __DIR__ . '/../' . $target_rel;
                if (!is_dir(dirname($target_abs))) {
                    mkdir(dirname($target_abs), 0755, true);
                }
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_abs)) {
                    $profileImage = $target_rel;
                }
            }
        }

        $result = $db->createUserRole(0, $role, $username, $password, [], $profileImage);
        if ($result['success']) {
            $successMsg = "User created successfully!";
        } else {
            $errorMsg = $result['message'];
        }
    } elseif (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $username = $_POST['username'];
        $password = $_POST['password']; // Optional password change
        $role = $_POST['role'];
        $profileImage = null;

        // Handle Profile Image Upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['png', 'jpg', 'jpeg'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_filename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target_rel = 'uploads/profiles/' . $new_filename;
                $target_abs = __DIR__ . '/../' . $target_rel;
                if (!is_dir(dirname($target_abs))) {
                    mkdir(dirname($target_abs), 0755, true);
                }
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_abs)) {
                    $profileImage = $target_rel;
                }
            }
        }

        $result = $db->updateUserRoleById($id, $role, $username, $password, [], $profileImage);
        if ($result['success']) {
            $successMsg = "User updated successfully!";
        } else {
            $errorMsg = $result['message'];
        }
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $result = $db->deleteUserRoleById($id);
        if ($result['success']) {
            $successMsg = "User deleted successfully!";
        } else {
            $errorMsg = $result['message'];
        }
    }
}

require_once '../includes/header.php';


$settings = $db->getSchoolSettings();
?>

<div class="px-4 md:px-8 py-8 w-full">
    <div class="max-w-[1600px] mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8 flex items-center gap-3">
            <div class="p-3 bg-teal-100 dark:bg-teal-900 rounded-lg text-teal-600 dark:text-teal-400">
                <i class="fas fa-cog"></i>
            </div>
            Settings
        </h2>

        <?php if ($successMsg): ?>
            <div class="bg-green-100 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 p-4 mb-6 rounded shadow-sm flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $successMsg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="bg-red-100 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 p-4 mb-6 rounded shadow-sm flex items-center justify-between" role="alert">
                 <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $errorMsg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Vertical Layout: Sidebar + Content -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <!-- Left Sidebar Navigation -->
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden sticky top-6">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-bold text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wide">Navigation</h3>
                    </div>
                    <nav class="p-2">
                        <a href="?tab=general" class="flex items-center gap-3 px-4 py-3 mb-1 rounded-lg text-sm font-medium transition-all <?php echo $activeTab === 'general' ? 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 border-l-4 border-teal-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'; ?>">
                            <i class="fas fa-school w-5 text-center"></i>
                            <span>General Settings</span>
                        </a>
                        <a href="?tab=users" class="flex items-center gap-3 px-4 py-3 mb-1 rounded-lg text-sm font-medium transition-all <?php echo $activeTab === 'users' ? 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 border-l-4 border-teal-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'; ?>">
                            <i class="fas fa-users-cog w-5 text-center"></i>
                            <span>User Management</span>
                        </a>
                        <a href="?tab=licensing" class="flex items-center gap-3 px-4 py-3 mb-1 rounded-lg text-sm font-medium transition-all <?php echo $activeTab === 'licensing' ? 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 border-l-4 border-teal-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'; ?>">
                            <i class="fas fa-key w-5 text-center"></i>
                            <span>Licensing</span>
                        </a>
                        <a href="?tab=updates" class="flex items-center gap-3 px-4 py-3 mb-1 rounded-lg text-sm font-medium transition-all <?php echo $activeTab === 'updates' ? 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 border-l-4 border-teal-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'; ?>">
                            <i class="fas fa-sync-alt w-5 text-center"></i>
                            <span>Software Updates</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Right Content Area -->
            <div class="flex-1 w-full bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden p-6 md:p-8">

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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full School Address</label>
                                <input type="text" name="school_address" value="<?php echo htmlspecialchars(isset($settings['school_address']) ? $settings['school_address'] : ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">School Contact No.</label>
                                <input type="text" name="school_contact" value="<?php echo htmlspecialchars(isset($settings['school_contact']) ? $settings['school_contact'] : ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SEMIS Code</label>
                                <input type="text" name="semis_code" value="<?php echo htmlspecialchars(isset($settings['semis_code']) ? $settings['semis_code'] : ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">PRINCIPAL Name / Signature Text</label>
                                <input type="text" name="headmaster_name" value="<?php echo htmlspecialchars($settings['headmaster_name']); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                <p class="text-xs text-gray-500 mt-1">Text to display at the bottom of reports (e.g. "PRINCIPAL Signature").</p>
                            </div>

                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">School Logo</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 border rounded-lg flex items-center justify-center bg-gray-50 overflow-hidden">
                                        <?php 
                                        $logoUrl = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
                                                   ? '../' . $settings['school_logo'] 
                                                   : '../GBPS_LOGO.png'; 
                                        ?>
                                        <img id="logo_preview" src="<?php echo $logoUrl; ?>?v=<?php echo time(); ?>" alt="Current Logo" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="school_logo" id="school_logo_input" accept="image/png, image/jpeg" onchange="previewLogo(this)"
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
                <?php elseif ($activeTab === 'users'): ?>
                    <div class="space-y-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">System Users</h3>
                                <p class="text-sm text-gray-500">Manage standalone users and their access roles.</p>
                            </div>
                            <button onclick="openAddUserModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg font-bold transition-colors flex items-center gap-2 shadow-sm">
                                <i class="fas fa-user-plus"></i> Add New User
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-100 shadow-sm">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                                    <tr>
                                        <th class="px-6 py-4">User</th>
                                        <th class="px-6 py-4">Role</th>
                                        <th class="px-6 py-4">Linked To</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <?php 
                                    $allRoles = $db->getAllUserRoles();
                                    foreach ($allRoles as $user): 
                                        $linkedTo = "Standalone Account";
                                        if ($user['teacher_id'] > 0) {
                                            $teacher = $db->getTeacher($user['teacher_id']);
                                            $linkedTo = $teacher ? htmlspecialchars($teacher['name']) . " (Teacher)" : "Unknown Teacher";
                                        }
                                    ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-700">
                                                        <?php if (!empty($user['profile_image'])): ?>
                                                            <img src="../<?php echo $user['profile_image']; ?>?v=<?php echo time(); ?>" alt="Avatar" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <i class="fas fa-user text-teal-600 dark:text-teal-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['username']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase <?php echo $user['role'] === 'Admin' ? 'bg-indigo-100 text-indigo-700' : ($user['role'] === 'Editor' ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-700'); ?>">
                                                    <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo $linkedTo; ?></td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button onclick='openEditUserModal(<?php echo json_encode($user); ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete User">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($allRoles)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 italic">No system users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                                <a href="https://web.facebook.com/rafeH.QAZI" target="_blank" class="text-slate-400 hover:text-blue-600 transition-colors" title="Facebook"><i class="fab fa-facebook text-lg"></i></a>
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
                        btn.innerHTML = '<i class="fas fa-cog fa-spin"></i> Backing up & Updating...';

                        // 1. Trigger Auto Backup Download First
                        const dlFrame = document.createElement('iframe');
                        dlFrame.style.display = 'none';
                        dlFrame.src = '../api/backup_data_auto.php';
                        document.body.appendChild(dlFrame);
                        
                        // 2. Small delay to allow browser to register download start before performance-heavy update
                        setTimeout(() => {
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
                                    console.error('Update Request Error:', err);
                                    alert('Network Error during update. This may happen if the backup is still processing. Please wait 10 seconds and try again.');
                                });
                        }, 3000); // Increased to 3s to ensure backup download is registered
                    }
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black/50 z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transition-all transform scale-95 opacity-0" id="addUserModalContent">
        <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-user-plus text-teal-600"></i> Add New System User
            </h3>
            <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="?tab=users" method="POST" class="p-6 space-y-4" enctype="multipart/form-data">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" required placeholder="User's login name"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="Min 4 characters" minlength="4"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
                    <option value="Admin">Admin (Full Access)</option>
                    <option value="Viewer">Viewer (Read Only)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture (Optional)</label>
                <input type="file" name="profile_image" accept="image/png, image/jpeg"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
                <button type="submit" name="add_user" class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg shadow-md transition-colors">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black/50 z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transition-all transform scale-95 opacity-0" id="editUserModalContent">
        <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-user-edit text-indigo-600"></i> Edit System User
            </h3>
            <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="?tab=users" method="POST" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" id="edit_username" required
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password (Leave blank to keep current)</label>
                <input type="password" name="password" placeholder="New password (optional)" minlength="4"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="edit_role" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500">
                    <option value="Admin">Admin (Full Access)</option>
                    <option value="Viewer">Viewer (Read Only)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Change Profile Picture (Optional)</label>
                <div class="flex items-center gap-4">
                    <div id="edit_user_avatar_preview" class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="file" name="profile_image" accept="image/png, image/jpeg" onchange="previewEditAvatar(this)"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
                <button type="submit" name="update_user" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Form (Hidden) -->
<form id="deleteUserForm" action="?tab=users" method="POST" class="hidden">
    <input type="hidden" name="user_id" id="delete_user_id">
    <input type="hidden" name="delete_user" value="1">
</form>

                </div> <!-- End of content area bg-white -->
            </div> <!-- End of flex-1 content column -->
        </div> <!-- End of flex layout (sidebar + content) -->
    </div> <!-- End of max-w-7xl mx-auto -->
</div> <!-- End of container -->

<script>
function openAddUserModal() {
    const modal = document.getElementById('addUserModal');
    const content = document.getElementById('addUserModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeAddUserModal() {
    const modal = document.getElementById('addUserModal');
    const content = document.getElementById('addUserModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function openEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_role').value = user.role;
    
    // Set preview
    const preview = document.getElementById('edit_user_avatar_preview');
    if (user.profile_image) {
        preview.innerHTML = `<img src="../${user.profile_image}?v=${Date.now()}" class="w-full h-full object-cover">`;
    } else {
        preview.innerHTML = `<i class="fas fa-user text-gray-400"></i>`;
    }
    
    const modal = document.getElementById('editUserModal');
    const content = document.getElementById('editUserModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeEditUserModal() {
    const modal = document.getElementById('editUserModal');
    const content = document.getElementById('editUserModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function confirmDeleteUser(id, username) {
    if (confirm('Are you sure you want to delete the user "' + username + '"? This action cannot be undone.')) {
        document.getElementById('delete_user_id').value = id;
        document.getElementById('deleteUserForm').submit();
    }
}

/**
 * LOGO PREVIEW LOGIC
 */
function previewLogo(input) {
    const preview = document.getElementById('logo_preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            // Add a small bounce effect for feedback
            preview.classList.add('scale-110');
            setTimeout(() => preview.classList.remove('scale-110'), 300);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewEditAvatar(input) {
    const preview = document.getElementById('edit_user_avatar_preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
