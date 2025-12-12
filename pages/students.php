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

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl md:text-3xl font-bold">Students Directory</h1>
        <p class="text-green-100 mt-1">Manage student records and admissions</p>
    </div>
    <a href="student_form.php" class="w-full md:w-auto bg-secondary text-white px-6 py-3 rounded-lg hover:bg-yellow-600 transition-colors shadow-md flex items-center justify-center gap-2 font-semibold">
        <i class="fas fa-plus-circle"></i> New Admission
    </a>
</div>

<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="mb-6">
        <form id="filterForm" action="" method="GET" class="flex flex-col md:flex-row flex-wrap gap-4 items-end" onsubmit="return false;">
            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-sm font-medium text-gray-700">Class</label>
                <select name="class" id="filter-class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">All Classes</option>
                    <?php
                    $classes = ['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'];
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

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
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
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-search text-4xl text-gray-300"></i>
                                <p>No students found matching your criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4 text-gray-700 font-medium"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                        <td class="p-4 text-gray-800 font-semibold capitalize">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($student['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover">
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
    const API_BASE_URL = '../api/';
</script>
<script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>

<?php include '../includes/footer.php'; ?>
