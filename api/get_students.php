<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$classFilter = isset($_GET['class']) ? $_GET['class'] : '';
$genderFilter = isset($_GET['gender']) ? $_GET['gender'] : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : '';
$order = isset($_GET['order']) ? $_GET['order'] : '';

$filters = [
    'search' => $search,
    'class' => $classFilter,
    'gender' => $genderFilter,
    'sort_by' => $sortBy,
    'order' => $order
];

$students = $db->filterStudents($filters);

// Check if JSON response is requested
if (isset($_GET['json']) && $_GET['json'] == '1') {
    ob_start();
}

if (empty($students)) {
    echo '<tr><td colspan="9" class="p-8 text-center text-gray-500"><div class="flex flex-col items-center gap-2"><i class="fas fa-search text-4xl text-gray-300"></i><p>No students found matching your criteria.</p></div></td></tr>';
} else {
    $serial = 1;
    foreach ($students as $student) {
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
        <?php
    }
}

if (isset($_GET['json']) && $_GET['json'] == '1') {
    $html = ob_get_clean();
    
    // Calculate stats
    $stats = [
        'total' => count($students),
        'gender' => ['Male' => 0, 'Female' => 0],
        'class' => []
    ];
    
    foreach ($students as $s) {
        $g = $s['gender'] ?: 'Unknown';
        if (!isset($stats['gender'][$g])) $stats['gender'][$g] = 0;
        $stats['gender'][$g]++;
        
        $c = $s['current_class'] ?: 'Unknown';
        if (!isset($stats['class'][$c])) $stats['class'][$c] = 0;
        $stats['class'][$c]++;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'stats' => $stats]);
    exit;
}
?>
