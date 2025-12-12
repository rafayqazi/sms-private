<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Check if user can access this page
if (!canAccessPage('assign_roles.php')) {
    header("Location: index.php");
    exit;
}

$db = new Database();
$teachers = $db->getAllTeachers();
$userRoles = $db->getAllUserRoles();

// Create a map of teacher_id => role info
$roleMap = [];
foreach ($userRoles as $role) {
    $roleMap[$role['teacher_id']] = $role;
}

$classes = ['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'];
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Assign User Roles</h1>
        <p class="text-gray-600">Assign login credentials and roles to teachers</p>
    </div>

    <!-- Global Success/Error Alert -->
    <div id="alertContainer" class="hidden mb-6">
        <div id="alertBox" class="p-4 rounded-lg"></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Teacher Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">CNIC</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Contact</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Current Role</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($teachers as $teacher): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800 capitalize"><?php echo htmlspecialchars($teacher['name']); ?></div>
                            <div class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($teacher['designation']); ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo htmlspecialchars(formatCnic($teacher['cnic'])); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo htmlspecialchars(formatContact($teacher['contact'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (isset($roleMap[$teacher['id']])): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                    <?php echo $roleMap[$teacher['id']]['role'] === 'Admin' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    <?php echo htmlspecialchars($roleMap[$teacher['id']]['role']); ?>
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    Username: <span class="font-medium"><?php echo htmlspecialchars($roleMap[$teacher['id']]['username']); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i class="fas fa-minus-circle mr-1"></i> No Role
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="openAssignModal(<?php echo htmlspecialchars(json_encode($teacher)); ?>, <?php echo isset($roleMap[$teacher['id']]) ? htmlspecialchars(json_encode($roleMap[$teacher['id']])) : 'null'; ?>)" 
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-user-shield mr-1"></i>
                                <?php echo isset($roleMap[$teacher['id']]) ? 'Edit Role' : 'Assign Role'; ?>
                            </button>
                            <?php if (isset($roleMap[$teacher['id']])): ?>
                            <button onclick="removeRole(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['name']); ?>')" 
                                class="ml-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash-alt mr-1"></i> Remove
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Role Modal -->
<!-- Moved to end of file and using explicit IDs for event listeners -->
<div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div id="modalContent" class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-user-shield text-indigo-600 mr-2"></i>
                    Assign User Role
                </h2>
                <button type="button" id="modalCloseBtn" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="assignRoleForm" class="p-6 space-y-6">
            <!-- Error Alert Inside Modal -->
            <div id="modalAlertContainer" class="hidden mb-4">
                <div id="modalAlertBox" class="p-4 rounded-lg"></div>
            </div>
            
            <input type="hidden" id="teacherId" name="teacherId">
            
            <!-- Teacher Name (Read-only) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Teacher Name</label>
                <input type="text" id="teacherName" readonly class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-700 font-medium">
            </div>

            <!-- Role Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Role *</label>
                <select id="role" name="role" required class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600 focus:ring-opacity-20 transition-all">
                    <option value="">Select Role</option>
                    <option value="Admin">Admin - Full Access</option>
                    <option value="Editor">Editor - Limited Access</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Admin: Full access to all features. Editor: Can only update attendance for assigned classes.</p>
            </div>

            <!-- Username -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Username *</label>
                <input type="text" id="username" name="username" required 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600 focus:ring-opacity-20 transition-all"
                    placeholder="Enter username">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password <span id="passwordOptional" class="text-gray-400">(leave empty to keep current)</span></label>
                <input type="password" id="password" name="password" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600 focus:ring-opacity-20 transition-all"
                    placeholder="Enter password">
            </div>

            <!-- Class Assignment -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">Assign Classes *</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($classes as $class): ?>
                    <label class="flex items-center space-x-2 p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-indigo-600 hover:bg-indigo-50 transition-all">
                        <input type="checkbox" name="classes[]" value="<?php echo $class; ?>" 
                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                        <span class="text-sm font-medium text-gray-700">Class <?php echo $class; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Select which classes this teacher can access (Editor role only)</p>
            </div>

            <!-- Admin Password Verification -->
            <div class="border-t pt-6">
                <label class="block text-sm font-semibold text-red-700 mb-2">
                    <i class="fas fa-lock mr-1"></i> Admin Password Verification *
                </label>
                <input type="password" id="adminPassword" name="adminPassword" required 
                    class="w-full px-4 py-3 border-2 border-red-300 rounded-lg focus:border-red-600 focus:ring-2 focus:ring-red-600 focus:ring-opacity-20 transition-all"
                    placeholder="Enter admin password">
                <p class="text-xs text-gray-500 mt-1">Enter your admin password to confirm this action</p>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-3">
                <button type="button" id="modalCancelBtn" 
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="flex-1 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span id="submitBtnText">Assign Role</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // State variables
    let isEditMode = false;
    let currentTeacherId = null;

    // DOM Elements
    const modal = document.getElementById('assignModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.getElementById('modalCloseBtn');
    const cancelBtn = document.getElementById('modalCancelBtn');
    const form = document.getElementById('assignRoleForm');
    
    // --- Event Listeners ---

    // Close Modal on X button click
    closeBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any default action
        closeModal();
    });

    // Close Modal on Cancel button click
    cancelBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any default action
        closeModal();
    });

    // Close Modal on Backdrop click
    modal.addEventListener('click', function(e) {
        // Only close if clicking the backdrop itself, not the content
        if (e.target === modal) {
            closeModal();
        }
    });

    // Prevent clicks inside modal from closing it
    modalContent.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Form Submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const teacherId = formData.get('teacherId');
        const role = formData.get('role');
        const username = formData.get('username');
        const password = formData.get('password');
        const adminPassword = formData.get('adminPassword');
        const classes = formData.getAll('classes[]');
        
        // Validate classes are selected
        if (classes.length === 0) {
            showAlert('Please select at least one class', 'error', true);
            return;
        }
        
        // Verify admin password first
        try {
            const verifyResponse = await fetch('../api/verify_admin_password.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ password: adminPassword })
            });
            
            const verifyResult = await verifyResponse.json();
            
            if (!verifyResult.success) {
                showAlert('Invalid admin password', 'error', true);
                return;
            }
            
            // Proceed with role assignment
            const assignResponse = await fetch('../api/assign_role.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    teacherId: teacherId,
                    role: role,
                    username: username,
                    password: password,
                    classes: classes,
                    isEdit: isEditMode
                })
            });
            
            if (!assignResponse.ok) {
                throw new Error(`HTTP error! status: ${assignResponse.status}`);
            }

            const responseText = await assignResponse.text();
            let assignResult;
            
            try {
                assignResult = JSON.parse(responseText);
            } catch (e) {
                console.error('Server response:', responseText);
                throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
            }
            
            if (assignResult.success) {
                showAlert(assignResult.message, 'success', true);
                // Close modal after a brief delay to show success
                setTimeout(() => {
                    closeModal();
                    location.reload();
                }, 1000);
            } else {
                showAlert(assignResult.message, 'error', true);
            }
            
        } catch (error) {
            showAlert('Error: ' + error.message, 'error', true);
            console.error(error);
        }
    });

    // --- Functions ---

    // Make openAssignModal global so it can be called from onclick in HTML
    window.openAssignModal = function(teacher, existingRole) {
        modal.classList.remove('hidden');
        document.getElementById('teacherId').value = teacher.id;
        document.getElementById('teacherName').value = teacher.name;
        
        // Reset form
        form.reset();
        document.getElementById('teacherId').value = teacher.id;
        document.getElementById('teacherName').value = teacher.name;
        
        // Clear all checkboxes
        document.querySelectorAll('input[name="classes[]"]').forEach(cb => cb.checked = false);
        
        if (existingRole) {
            isEditMode = true;
            currentTeacherId = teacher.id;
            document.getElementById('role').value = existingRole.role;
            document.getElementById('username').value = existingRole.username;
            document.getElementById('passwordOptional').classList.remove('hidden');
            document.getElementById('password').removeAttribute('required');
            document.getElementById('submitBtnText').textContent = 'Update Role';
            
            // Check assigned classes
            if (existingRole.assigned_classes) {
                existingRole.assigned_classes.forEach(className => {
                    const checkbox = document.querySelector(`input[name="classes[]"][value="${className}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        } else {
            isEditMode = false;
            currentTeacherId = null;
            document.getElementById('passwordOptional').classList.add('hidden');
            document.getElementById('password').setAttribute('required', 'required');
            document.getElementById('submitBtnText').textContent = 'Assign Role';
        }
    };

    // Close Modal Function
    function closeModal() {
        modal.classList.add('hidden');
        form.reset();
        // Clear modal errors
        document.getElementById('modalAlertContainer').classList.add('hidden');
    }

    // Show Alert Function
    function showAlert(message, type, inModal = false) {
        let container, box;

        if (inModal) {
            container = document.getElementById('modalAlertContainer');
            box = document.getElementById('modalAlertBox');
        } else {
            container = document.getElementById('alertContainer');
            box = document.getElementById('alertBox');
        }
        
        container.classList.remove('hidden');
        box.className = 'p-4 rounded-lg flex items-center gap-3';
        
        if (type === 'success') {
            box.classList.add('bg-green-50', 'border', 'border-green-200', 'text-green-800');
            box.innerHTML = `<i class="fas fa-check-circle text-green-600"></i><span>${message}</span>`;
        } else {
            box.classList.add('bg-red-50', 'border', 'border-red-200', 'text-red-800');
            box.innerHTML = `<i class="fas fa-exclamation-circle text-red-600"></i><span>${message}</span>`;
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            container.classList.add('hidden');
        }, 5000);
    }

    // Make removeRole global
    window.removeRole = async function(teacherId, teacherName) {
        if (!confirm(`Are you sure you want to remove the role from ${teacherName}?`)) {
            return;
        }
        
        const adminPassword = prompt('Enter admin password to confirm:');
        if (!adminPassword) return;
        
        try {
            // Verify admin password
            const verifyResponse = await fetch('../api/verify_admin_password.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ password: adminPassword })
            });
            
            const verifyResult = await verifyResponse.json();
            
            if (!verifyResult.success) {
                showAlert('Invalid admin password', 'error', false);
                return;
            }
            
            // Remove role
            const removeResponse = await fetch('../api/remove_role.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ teacherId: teacherId })
            });
            
            const removeResult = await removeResponse.json();
            
            if (removeResult.success) {
                showAlert(removeResult.message, 'success', false);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(removeResult.message, 'error', false);
            }
            
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'error', false);
            console.error(error);
        }
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>
