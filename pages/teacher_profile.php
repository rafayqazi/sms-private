<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('teacher_profile.php')) {
    header("Location: index.php");
    exit;
}
$db = new Database();

$teacher = null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $teacher = $db->getTeacher($id);
} else {
    $allTeachers = $db->getAllTeachers();
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-4 md:p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl font-bold">Teacher Profile</h1>
        <p class="text-green-100 mt-1">Manage teaching staff details</p>
    </div>
    <a href="../index.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium w-full md:w-auto">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
</div>

<?php if ($id && $teacher): ?>
    <!-- Detailed Profile View -->
    <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 max-w-7xl mx-auto">
        <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <a href="teacher_profile.php" class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-md transition duration-200 flex items-center justify-center gap-2 w-full md:w-auto">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <a href="teacher_form.php?edit=<?php echo $teacher['id']; ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="../api/delete_teacher.php?id=<?php echo $teacher['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition duration-200 flex items-center justify-center gap-2" onclick="return confirm('Are you sure you want to delete this teacher? This action cannot be undone.');">
                    <i class="fas fa-trash"></i> Delete Profile
                </a>
            </div>
        </div>
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Profile Image Section -->
            <div class="w-full md:w-1/4 text-center">
                <div class="profile-image-container mb-4">
                    <?php 
                    $imagePath = !empty($teacher['profile_image']) ? $teacher['profile_image'] : '';
                    if (!empty($imagePath)) {
                        // Check if file exists relative to current script (pages/teacher_profile.php)
                        // If path starts with '../', it's already relative to pages/
                        if (file_exists($imagePath)) {
                            // Good to go
                        } 
                        // If path is stored as 'uploads/...' (root relative), prepend '../'
                        elseif (file_exists('../' . $imagePath)) {
                            $imagePath = '../' . $imagePath;
                        } else {
                            $imagePath = ''; // File not found
                        }
                    }
                    ?>
                    <?php if (!empty($imagePath)): ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Profile Image" class="rounded-lg shadow-md w-full h-auto max-h-[300px] object-cover object-top mx-auto">
                    <?php else: ?>
                        <div class="bg-gray-200 rounded-lg flex items-center justify-center h-[200px] w-full mx-auto">
                            <i class="fas fa-user fa-4x text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($teacher['name']); ?></h2>
                <p class="text-gray-500"><?php echo htmlspecialchars($teacher['designation']); ?></p>
            </div>

            <!-- Details Section -->
            <div class="w-full md:w-3/4">
                <h3 class="mb-4 border-b pb-2 text-lg font-semibold">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div><strong>Father Name:</strong> <?php echo htmlspecialchars($teacher['father_name']); ?></div>
                    <div><strong>Gender:</strong> <?php echo htmlspecialchars($teacher['gender']); ?></div>
                    <div><strong>CNIC:</strong> <?php echo formatCnic($teacher['cnic']); ?></div>
                    <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($teacher['dob']); ?></div>
                    <div><strong>Age:</strong> <?php echo htmlspecialchars($teacher['age']); ?></div>
                    <div><strong>Contact:</strong> <?php echo formatContact($teacher['contact']); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></div>
                    <div><strong>Address:</strong> <?php echo htmlspecialchars($teacher['address']); ?></div>
                </div>

                <h3 class="mb-4 border-b pb-2 text-lg font-semibold mt-6">Professional Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div><strong>Department:</strong> <?php echo htmlspecialchars($teacher['department']); ?></div>
                    <div><strong>Posting:</strong> <?php echo htmlspecialchars($teacher['posting']); ?></div>
                    <div><strong>Date of Joining:</strong> <?php echo isset($teacher['joining_date']) ? htmlspecialchars($teacher['joining_date']) : 'N/A'; ?></div>
                    <div><strong>Basic Scale:</strong> <?php echo htmlspecialchars($teacher['basic_scale']); ?></div>
                    <div><strong>Date of Retirement:</strong> <?php echo htmlspecialchars($teacher['retirement_date']); ?></div>
                </div>

                <h3 class="mb-4 border-b pb-2 text-lg font-semibold mt-6">Financial Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Payment Type:</strong> <?php echo htmlspecialchars($teacher['payment_type']); ?></div>
                    <div><strong>Payment No:</strong> <?php echo htmlspecialchars($teacher['payment_no']); ?></div>
                    <div><strong>IBAN:</strong> <?php echo htmlspecialchars($teacher['iban']); ?></div>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($id && !$teacher): ?>
    <div class="bg-red-50 text-red-600 p-4 rounded-lg text-center border border-red-200">Teacher not found.</div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showModal('error', 'Error', 'Teacher not found.');
        });
    </script>
    <div class="text-center mt-4">
        <a href="teacher_profile.php" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-green-800 transition-colors">View All Teachers</a>
    </div>
<?php else: ?>
    <!-- List View -->
    <div class="bg-white shadow-lg rounded-lg p-6 max-w-7xl mx-auto">
        
        <!-- Bulk Actions & Filters -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <div class="flex items-center gap-2 w-full md:w-auto">
                <select id="bulkActionSelect" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <button id="applyBulkAction" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-green-800 transition duration-300 text-sm">Apply</button>
            </div>
            <!-- Potential existing filters or search could go here or align right -->
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-3 w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4">
                        </th>
                        <th class="p-3 font-semibold text-gray-700">S#</th>
                        <th class="p-3 font-semibold text-gray-700">ID</th>
                        <th class="p-3 font-semibold text-gray-700">Name</th>
                        <th class="p-3 font-semibold text-gray-700">Designation</th>
                        <th class="p-3 font-semibold text-gray-700">Department</th>
                        <th class="p-3 font-semibold text-gray-700">Contact</th>
                        <th class="p-3 font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($allTeachers) > 0): ?>
                        <?php $i = 1; foreach ($allTeachers as $t): ?>
                            <tr class="border-b hover:bg-gray-50 transition-colors">
                                <td class="p-3">
                                    <input type="checkbox" name="teacher_ids[]" value="<?php echo htmlspecialchars($t['id']); ?>" class="teacher-checkbox rounded border-gray-300 text-primary focus:ring-primary h-4 w-4">
                                </td>
                                <td class="p-3 font-mono text-gray-600"><?php echo $i++; ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($t['id']); ?></td>
                                <td class="p-3">
                                    <div class="flex items-center">
                                        <?php if (!empty($t['profile_image']) && file_exists($t['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($t['profile_image']); ?>" alt="" class="rounded-full w-8 h-8 mr-2 object-cover object-top">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-gray-200 mr-2 flex items-center justify-center text-gray-500">
                                                <i class="fas fa-user text-xs"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($t['name']); ?>
                                    </div>
                                </td>
                                <td class="p-3 capitalize"><?php echo htmlspecialchars($t['designation']); ?></td>
                                <td class="p-3 capitalize"><?php echo htmlspecialchars($t['department']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($t['contact']); ?></td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <a href="teacher_profile.php?id=<?php echo $t['id']; ?>" class="text-blue-500 hover:text-blue-700 transition-colors" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="teacher_form.php?edit=<?php echo $t['id']; ?>" class="text-yellow-500 hover:text-yellow-700 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../api/delete_teacher.php?id=<?php echo $t['id']; ?>" class="text-red-500 hover:text-red-700 transition-colors" title="Delete" onclick="return confirm('Are you sure you want to delete this teacher?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No teachers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.teacher-checkbox');
            const applyBtn = document.getElementById('applyBulkAction');
            const actionSelect = document.getElementById('bulkActionSelect');

            // Select All Logic
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });

            // Update Select All if individual checkbox changed
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (!this.checked) {
                        selectAll.checked = false;
                    } else {
                        const allChecked = Array.from(checkboxes).every(c => c.checked);
                        if (allChecked) selectAll.checked = true;
                    }
                });
            });

            // Apply Action Logic
            applyBtn.addEventListener('click', function() {
                const action = actionSelect.value;
                if (!action) {
                    showModal('warning', 'Action Required', 'Please select an action.');
                    return;
                }

                const selectedIds = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedIds.length === 0) {
                    showModal('warning', 'Selection Required', 'Please select at least one item.');
                    return;
                }

                if (action === 'delete') {
                    showConfirmationModal(
                        'Confirm Deletion',
                        `Are you sure you want to delete ${selectedIds.length} teacher(s)? This action cannot be undone.`,
                        function() {
                            fetch('../api/bulk_action.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    type: 'teacher',
                                    action: 'delete',
                                    ids: selectedIds
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    showModal('success', 'Success', data.message);
                                    setTimeout(() => location.reload(), 1500);
                                } else {
                                    showModal('error', 'Error', data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showModal('error', 'Error', 'An error occurred while processing your request.');
                            });
                        }
                    );
                }
            });
        });
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
