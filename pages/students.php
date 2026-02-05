<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('students.php')) {
    header("Location: index.php");
    exit;
}

$db = new Database();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$classFilter = isset($_GET['class']) ? $_GET['class'] : '';
$genderFilter = isset($_GET['gender']) ? $_GET['gender'] : '';

$filters = [
    'search' => $search,
    'class' => $classFilter,
    'gender' => $genderFilter
];

$students = $db->filterStudents($filters);
?>

<?php include '../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl md:text-3xl font-bold">Students Directory</h1>
        <p class="text-green-100 mt-1">Manage student records and admissions</p>
    </div>
    <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
        <a href="bulk_admission.php" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors shadow-md flex items-center justify-center gap-2 font-semibold">
            <i class="fas fa-file-import"></i> Bulk Admission
        </a>
        <a href="student_form.php" class="bg-secondary text-white px-6 py-3 rounded-lg hover:bg-yellow-600 transition-colors shadow-md flex items-center justify-center gap-2 font-semibold">
            <i class="fas fa-plus-circle"></i> New Admission
        </a>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-indigo-500">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-venus-mars text-indigo-500"></i> Gender Distribution
        </h3>
        <div class="relative h-64">
            <canvas id="genderChart"></canvas>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-green-500">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-users-class text-green-500"></i> Class Distribution
        </h3>
        <div class="relative h-64">
            <canvas id="classChart"></canvas>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="mb-6">
        <form id="filterForm" action="" method="GET" class="flex flex-col md:flex-row flex-wrap gap-4 items-end" onsubmit="return false;">
            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-sm font-medium text-gray-700">Class</label>
                <select name="class" id="filter-class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">All Classes</option>
                    <?php
                    $classes = $db->getClassNames();
                    foreach ($classes as $c) {
                        $selected = ($classFilter == $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-sm font-medium text-gray-700">Gender</label>
                <select name="gender" id="filter-gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">All Genders</option>
                    <option value="Male" <?php echo ($genderFilter == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($genderFilter == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 flex-grow">
                <label class="text-sm font-medium text-gray-700">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" id="filter-search" placeholder="Name or GR No..." value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
            </div>
            
            <!-- Hidden button to prevent form submission on enter -->
            <button type="submit" class="hidden">Filter</button>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <div class="flex items-center gap-3 w-full md:w-auto">
            <select id="bulkActionSelect" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white shadow-sm">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete Selected</option>
                <option value="mark_alumni">Mark as Alumni</option>
                <option value="mark_active">Mark as Active</option>
                <option value="mark_repeater">Mark as Repeater</option>
                <option value="unmark_repeater">Unmark as Repeater</option>
                <option value="generate_ids">Generate ID Cards</option>
            </select>
            <button id="applyBulkAction" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-300 text-sm font-semibold shadow-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i> Apply
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                    <th class="p-4 w-10">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                    </th>
                    <th class="p-4 text-gray-500 font-semibold">S#</th>
                    <th id="sort-gr" class="p-4 cursor-pointer hover:bg-gray-100 transition-colors select-none group">
                        <div class="flex items-center gap-2">
                            GR No <i class="fas fa-sort text-gray-400 group-hover:text-gray-600" id="sort-icon"></i>
                        </div>
                    </th>
                    <th class="p-4">Student Name</th>
                    <th class="p-4">Father Name</th>
                    <th class="p-4">Gender</th>
                    <th class="p-4">Class</th>
                    <th class="p-4">Admission Date</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody id="students-table-body" class="divide-y divide-gray-100">
                <?php if (empty($students)): ?>
                    <tr>
                    <tr>
                        <td colspan="9" class="p-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-search text-4xl text-gray-300"></i>
                                <p>No students found matching your criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $serial = 1;
                    foreach ($students as $student): 
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4">
                            <input type="checkbox" name="student_ids[]" value="<?php echo htmlspecialchars($student['id']); ?>" class="student-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        </td>
                        <td class="p-4 text-gray-500 font-medium"><?php echo $serial++; ?></td>
                        <td class="p-4 text-gray-700 font-medium"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                        <td class="p-4 text-gray-800 font-semibold capitalize">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($student['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" 
                                                 alt="" 
                                                 class="h-8 w-8 rounded-full border-2 border-white shadow-sm object-cover object-top">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                        <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($student['student_name']); ?>
                            </div>
                        </td>
                        <td class="p-4 text-gray-600 capitalize"><?php echo htmlspecialchars($student['father_name']); ?></td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $student['gender'] == 'Male' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'; ?>">
                                <?php echo htmlspecialchars($student['gender']); ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    <?php echo htmlspecialchars($student['current_class']); ?>
                                </span>
                                <?php if (isset($student['is_repeater']) && $student['is_repeater'] == '1'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700" title="Repeater">
                                        <i class="fas fa-redo text-xs"></i> Repeater
                                    </span>
                                <?php endif; ?>
                                <?php if (isset($student['student_status']) && $student['student_status'] == 'Alumni'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700" title="Alumni">
                                        <i class="fas fa-graduation-cap text-xs"></i> Alumni
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-4 text-gray-500 text-sm"><?php echo htmlspecialchars($student['admission_date']); ?></td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="text-blue-500 hover:text-blue-700 transition-colors" title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="student_form.php?id=<?php echo $student['id']; ?>" class="text-yellow-500 hover:text-yellow-700 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../api/delete_student.php?id=<?php echo $student['id']; ?>" class="text-red-500 hover:text-red-700 transition-colors" title="Delete" onclick="return confirm('Are you sure you want to delete this student?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const applyBtn = document.getElementById('applyBulkAction');
        const actionSelect = document.getElementById('bulkActionSelect');

        // Select All Logic - Query dynamically to handle filtered results
        if(selectAll) {
            selectAll.addEventListener('change', function() {
                const currentCheckboxes = document.querySelectorAll('.student-checkbox');
                currentCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        // Apply Action Logic
        if(applyBtn) {
            applyBtn.addEventListener('click', function() {
                const action = actionSelect.value;
                if (!action) {
                    showModal('warning', 'Action Required', 'Please select an action.');
                    return;
                }

                const currentCheckboxes = document.querySelectorAll('.student-checkbox');
                const selectedIds = Array.from(currentCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedIds.length === 0) {
                    showModal('warning', 'Selection Required', 'Please select at least one item.');
                    return;
                }

                if (action === 'delete' || action === 'mark_alumni' || action === 'mark_active' || action === 'mark_repeater' || action === 'unmark_repeater') {
                    let confirmTitle = 'Confirm Action';
                    let confirmMsg = `Are you sure you want to apply this action to ${selectedIds.length} student(s)?`;
                    
                    if (action === 'delete') {
                        confirmTitle = 'Confirm Deletion';
                        confirmMsg = `Are you sure you want to delete ${selectedIds.length} student(s)? This action cannot be undone.`;
                    }

                    showConfirmationModal(
                        confirmTitle,
                        confirmMsg,
                        function() {
                            fetch('../api/bulk_action.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    type: 'student',
                                    action: action,
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
                } else if (action === 'generate_ids') {
                    // Collect GR Numbers for selected IDs
                    const selectedGrNos = Array.from(currentCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => {
                            const row = cb.closest('tr');
                            return row.cells[2].textContent.trim(); // GR No is in the 3rd column (index 2)
                        });
                    
                    const url = `generate_id_card.php?gr_no=${selectedGrNos.join(',')}`;
                    window.open(url, '_blank');
                }
            });
        }
    });
</script>

<script>
    const API_BASE_URL = '../api/';
</script>
<script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>

<?php include '../includes/footer.php'; ?>
