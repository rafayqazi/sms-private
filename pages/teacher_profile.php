<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
$isTeacherSelf = isEditor() && isset($_SESSION['teacher_id']) && isset($_GET['id']) && $_GET['id'] == $_SESSION['teacher_id'];
$isTeacherView = isEditor() && isset($_SESSION['teacher_id']);

if (!canAccessPage('teacher_profile.php') && !$isTeacherSelf) {
    header("Location: ../index.php");
    exit;
}
$db = new Database();

$teacher = null;
$id = isset($_GET['id']) ? $_GET['id'] : null;
$salaryHistory = [];

if ($id) {
    $teacher = $db->getTeacher($id);
    if (!$teacher) {
        header("Location: teacher_profile.php");
        exit;
    }
    $salaryHistory = $db->getTeacherSalaryHistory($id);
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
    <div class="flex gap-2 w-full md:w-auto">
        <?php if (!$isTeacherView): ?>
        <a href="teacher_form.php" class="bg-white text-primary border border-white px-6 py-2 rounded-md hover:bg-green-50 transition duration-300 flex items-center justify-center gap-2 font-medium w-full md:w-auto">
            <i class="fas fa-plus-circle"></i> Add New Teacher
        </a>
        <?php endif; ?>
        <a href="../index.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium w-full md:w-auto">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<?php if ($id && $teacher): ?>
    <!-- Detailed Profile View -->
    <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 max-w-7xl mx-auto">
        <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <?php if (!$isTeacherView): ?>
            <a href="teacher_profile.php" class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-md transition duration-200 flex items-center justify-center gap-2 w-full md:w-auto">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <?php else: ?>
            <div></div> <!-- Spacer -->
            <?php endif; ?>
            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <?php if (!$isTeacherView): ?>
                <a href="teacher_form.php?edit=<?php echo $teacher['id']; ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="../api/delete_teacher.php?id=<?php echo $teacher['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition duration-200 flex items-center justify-center gap-2" onclick="return confirm('Are you sure you want to delete this teacher? This action cannot be undone.');">
                    <i class="fas fa-trash"></i> Delete Profile
                </a>
                <?php endif; ?>
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

                <h3 class="mb-4 border-b pb-2 text-lg font-semibold mt-6 text-indigo-600"><i class="fas fa-key mr-2"></i>Login Credentials</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-indigo-50 p-4 rounded-xl border border-indigo-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-indigo-100 opacity-20 group-hover:opacity-40 transition-opacity">
                        <i class="fas fa-shield-alt fa-8x"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] uppercase font-black text-indigo-400 tracking-widest mb-1">Username (CNIC)</p>
                        <div class="flex items-center gap-2">
                             <span class="text-sm font-bold text-indigo-900 bg-white px-3 py-1.5 rounded-lg border border-indigo-100 shadow-sm"><?php echo formatCnic($teacher['cnic']); ?></span>
                             <button onclick="copyToClipboard('<?php echo str_replace('-', '', $teacher['cnic']); ?>')" class="text-indigo-400 hover:text-indigo-600 transition-colors" title="Copy Raw CNIC">
                                 <i class="far fa-copy text-xs"></i>
                             </button>
                        </div>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] uppercase font-black text-indigo-400 tracking-widest mb-1">Password (DOB)</p>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-indigo-900 bg-white px-3 py-1.5 rounded-lg border border-indigo-100 shadow-sm"><?php echo htmlspecialchars($teacher['dob']); ?></span>
                            <button onclick="copyToClipboard('<?php echo $teacher['dob']; ?>')" class="text-indigo-400 hover:text-indigo-600 transition-colors" title="Copy DOB">
                                 <i class="far fa-copy text-xs"></i>
                             </button>
                        </div>
                    </div>
                    <div class="col-span-full pt-2 mt-2 border-t border-indigo-100/50">
                        <p class="text-[10px] text-indigo-500/70 italic flex items-center gap-1.5">
                            <i class="fas fa-info-circle text-[10px]"></i>
                            Authorized teachers use these credentials to log in via the <strong>Teacher Portal</strong> Mode.
                        </p>
                    </div>
                </div>

                <script>
                function copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        // Use the existing showModal if available, or just a simple alert
                        if (typeof showModal === 'function') {
                            showModal('success', 'Copied', 'Copied to clipboard: ' + text);
                        } else {
                            alert('Copied: ' + text);
                        }
                    });
                }
                </script>

                <h3 class="mb-4 border-b pb-2 text-lg font-semibold mt-6">Financial Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-wrap items-center justify-between col-span-full bg-gray-50 p-3 rounded-lg border border-gray-100 mb-2 gap-3">
                        <div>
                            <strong>Monthly Salary:</strong> 
                            <span class="text-lg font-bold text-gray-900 ml-2">Rs. <?php echo number_format($teacher['salary'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex gap-2">
                            <?php 
                            $currentMonth = date('Y-m');
                            $isPaidThisMonth = false;
                            foreach ($salaryHistory as $h) {
                                if ($h['month'] === $currentMonth) { $isPaidThisMonth = true; break; }
                            }
                            if (!$isPaidThisMonth && ($teacher['salary'] ?? 0) > 0): 
                            ?>
                                <?php if (!$isTeacherView): ?>
                                <button onclick="openPayModal('<?php echo $id; ?>', '<?php echo htmlspecialchars($teacher['name']); ?>', <?php echo $teacher['salary']; ?>)" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold text-sm px-4 py-2 rounded-lg shadow-sm transition-all hover:shadow-md">
                                    <i class="fas fa-money-bill-wave mr-2"></i> Pay Now (<?php echo date('M'); ?>)
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button onclick="openHistoryModal()" class="text-indigo-600 hover:text-indigo-800 font-bold text-sm bg-white px-4 py-2 rounded-lg border border-indigo-100 shadow-sm transition-all hover:shadow-md">
                                <i class="fas fa-history mr-2"></i> View Payment History
                            </button>
                        </div>
                    </div>
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

        <div class="mb-4">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary h-5 w-5 cursor-pointer">
                <label for="selectAll" class="text-gray-600 font-bold text-sm cursor-pointer select-none">Select All Teachers For Bulk Action</label>
            </div>
        </div>

        <?php if (count($allTeachers) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($allTeachers as $t): ?>
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 p-6 relative group overflow-hidden border-b-4 border-b-transparent hover:border-b-indigo-500 h-full flex flex-col">
                        <!-- Bulk Selection -->
                        <div class="absolute top-4 left-4 z-10">
                            <input type="checkbox" name="teacher_ids[]" value="<?php echo htmlspecialchars($t['id']); ?>" class="teacher-checkbox rounded-full border-gray-300 text-primary focus:ring-primary h-5 w-5 cursor-pointer transition-transform hover:scale-110">
                        </div>

                        <!-- Top Toolbar (Visual Only, Actions grouped below) -->
                        <div class="absolute top-4 right-4 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="teacher_profile.php?id=<?php echo $t['id']; ?>" class="bg-indigo-50 text-indigo-600 p-2 rounded-xl hover:bg-indigo-100 transition-colors shadow-sm" title="Quick View">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>

                        <!-- Card Content -->
                        <div class="flex flex-col items-center flex-grow">
                            <!-- Avatar -->
                            <div class="relative mb-6">
                                <?php 
                                $imgFile = !empty($t['profile_image']) ? $t['profile_image'] : '';
                                if (!empty($imgFile) && !file_exists($imgFile)) {
                                    if (file_exists('../' . $imgFile)) $imgFile = '../' . $imgFile;
                                    else $imgFile = '';
                                }
                                ?>
                                <?php if (!empty($imgFile)): ?>
                                    <img src="<?php echo htmlspecialchars($imgFile); ?>" alt="" class="w-28 h-28 rounded-3xl object-cover object-top border-4 border-white shadow-lg group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-28 h-28 rounded-3xl bg-gradient-to-br from-indigo-50 to-indigo-100 flex items-center justify-center text-indigo-500 shadow-inner group-hover:rotate-3 transition-transform">
                                        <i class="fas fa-user-tie text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute -bottom-2 -right-2 bg-indigo-600 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-md uppercase tracking-tighter">
                                    ID: <?php echo htmlspecialchars($t['id']); ?>
                                </div>
                            </div>
                            
                            <!-- Name & Title -->
                            <div class="text-center mb-6">
                                <h3 class="font-black text-gray-800 text-lg mb-1 leading-tight group-hover:text-indigo-600 transition-colors"><?php echo htmlspecialchars($t['name']); ?></h3>
                                <div class="flex items-center justify-center gap-2">
                                    <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-widest rounded-full shadow-sm">
                                        <?php echo htmlspecialchars($t['designation']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Meta Info -->
                            <div class="w-full space-y-3 mb-6">
                                <div class="flex items-start gap-3 bg-gray-50/50 p-3 rounded-2xl border border-gray-100/50 hover:bg-white transition-colors duration-300">
                                    <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-building text-indigo-400 text-xs text-center"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tight mb-0.5">Department</p>
                                        <p class="text-xs text-gray-700 font-semibold truncate"><?php echo htmlspecialchars($t['department']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 bg-gray-50/50 p-3 rounded-2xl border border-gray-100/50 hover:bg-white transition-colors duration-300">
                                    <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-phone-alt text-indigo-400 text-xs text-center"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tight mb-0.5">Contact</p>
                                        <p class="text-xs text-gray-700 font-semibold"><?php echo htmlspecialchars($t['contact']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-3 gap-2 mt-auto pt-4 border-t border-gray-100">
                            <a href="teacher_profile.php?id=<?php echo $t['id']; ?>" class="bg-indigo-600 text-white p-2.5 rounded-xl hover:bg-indigo-700 flex items-center justify-center shadow-md hover:shadow-indigo-200 transition-all font-bold text-xs gap-1.5" title="Full Bio">
                                <i class="fas fa-id-card"></i>
                            </a>
                            <a href="teacher_form.php?edit=<?php echo $t['id']; ?>" class="bg-amber-500 text-white p-2.5 rounded-xl hover:bg-amber-600 flex items-center justify-center shadow-md hover:shadow-amber-200 transition-all font-bold text-xs gap-1.5" title="Update">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="../api/delete_teacher.php?id=<?php echo $t['id']; ?>" class="bg-rose-500 text-white p-2.5 rounded-xl hover:bg-rose-600 flex items-center justify-center shadow-md hover:shadow-rose-200 transition-all font-bold text-xs gap-1.5" title="Terminate" onclick="return confirm('Are you sure you want to delete this teacher?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 rounded-3xl p-20 text-center border-2 border-dashed border-gray-200">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users-slash text-gray-300 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Teachers Found</h3>
                <p class="text-gray-500 mb-6">It seems your staff directory is empty.</p>
                <a href="teacher_form.php" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-200">
                    <i class="fas fa-plus-circle mr-2"></i> Add First Teacher
                </a>
            </div>
        <?php endif; ?>
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

    <!-- Salary History Modal -->
    <div id="historyModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col animate-modal-up">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-xl font-bold text-gray-900">Salary Payment History</h3>
                <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 text-sm">
                <?php if (empty($salaryHistory)): ?>
                    <div class="text-center py-12 text-gray-400 italic">No payment history found for this teacher.</div>
                <?php else: ?>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-6 py-4">Month</th>
                                <th class="px-6 py-4">Base</th>
                                <th class="px-6 py-4">Deduction</th>
                                <th class="px-6 py-4">Net Paid</th>
                                <th class="px-6 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            // Sort history by date descending
                            usort($salaryHistory, function($a, $b) {
                                return strcmp($b['payment_date'], $a['payment_date']);
                            });
                            foreach ($salaryHistory as $h): 
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-800"><?php echo date('F Y', strtotime($h['month'])); ?></td>
                                <td class="px-6 py-4">Rs. <?php echo number_format($h['base_salary'], 2); ?></td>
                                <td class="px-6 py-4 text-red-500">
                                    <?php echo $h['deduction'] > 0 ? ('-Rs. ' . number_format($h['deduction'], 2)) : '-'; ?>
                                    <?php if (!empty($h['notes'])): ?>
                                        <div class="text-[10px] text-gray-400 font-normal italic"><?php echo htmlspecialchars($h['notes']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-bold text-green-600">Rs. <?php echo number_format($h['net_salary'], 2); ?></td>
                                <td class="px-6 py-4 text-gray-500"><?php echo date('d M Y', strtotime($h['payment_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="p-6 border-t border-gray-100 bg-gray-50 text-right">
                <button onclick="closeHistoryModal()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition-colors shadow-sm">Close</button>
            </div>
        </div>
    </div>

    <script>
    function openHistoryModal() {
        document.getElementById('historyModal').classList.remove('hidden');
    }
    function closeHistoryModal() {
        document.getElementById('historyModal').classList.add('hidden');
    }
    </script>

    <!-- Pay Salary Modal -->
    <div id="payModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md animate-modal-up overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">Pay Salary</h3>
                <p id="pay_teacher_name" class="text-sm text-indigo-600 font-medium"></p>
            </div>
            <form id="paySalaryForm" class="p-6 space-y-4">
                <input type="hidden" name="teacher_id" id="pay_teacher_id">
                <input type="hidden" name="base_salary" id="pay_base_salary">
                <input type="hidden" name="month" value="<?php echo date('Y-m'); ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Salary</label>
                    <div class="text-lg font-bold text-gray-900" id="display_base_salary"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deduction (if any)</label>
                    <input type="number" name="deduction" id="pay_deduction" value="0" step="0.01" 
                           class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                           oninput="calculateNetSalary()">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Net Payable</label>
                    <div class="text-xl font-black text-green-600" id="display_net_salary"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Reason for Deduction</label>
                    <textarea name="notes" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500" rows="2" placeholder="e.g. 1 day leave deducted"></textarea>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closePayModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openHistoryModal() {
        document.getElementById('historyModal').classList.remove('hidden');
    }
    function closeHistoryModal() {
        document.getElementById('historyModal').classList.add('hidden');
    }
    function openPayModal(id, name, salary) {
        document.getElementById('pay_teacher_id').value = id;
        document.getElementById('pay_teacher_name').innerText = 'Paying for: ' + name;
        document.getElementById('pay_base_salary').value = salary;
        document.getElementById('display_base_salary').innerText = 'Rs. ' + salary.toLocaleString();
        document.getElementById('pay_deduction').value = 0;
        calculateNetSalary();
        document.getElementById('payModal').classList.remove('hidden');
    }
    function closePayModal() {
        document.getElementById('payModal').classList.add('hidden');
    }
    function calculateNetSalary() {
        const base = parseFloat(document.getElementById('pay_base_salary').value || 0);
        const ded = parseFloat(document.getElementById('pay_deduction').value || 0);
        const net = base - ded;
        document.getElementById('display_net_salary').innerText = 'Rs. ' + net.toLocaleString();
    }

    document.getElementById('paySalaryForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('../api/pay_salary.php', { // Path adjusted for pages/ folder
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Salary paid successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    };
    </script>

<?php include '../includes/footer.php'; ?>
