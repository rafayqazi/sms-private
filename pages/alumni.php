<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('alumni.php')) {
    header("Location: index.php");
    exit;
}
$db = new Database();

// Get all alumni students
$allStudents = $db->readData();
$alumniStudents = array_filter($allStudents, function($student) {
    return isset($student['student_status']) && $student['student_status'] === 'Alumni';
});

// Sort by GR number
usort($alumniStudents, function($a, $b) {
    return (int)$a['gr_no'] - (int)$b['gr_no'];
});
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">Alumni Students</h1>
        <p class="text-green-100 mt-1">Graduates who successfully completed their primary education</p>
    </div>
    <div class="text-center md:text-right w-full md:w-auto p-3 bg-white/10 rounded-lg">
        <div class="text-4xl font-bold"><?php echo count($alumniStudents); ?></div>
        <div class="text-sm text-green-100">Total Alumni</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6">
    <?php if (empty($alumniStudents)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    No alumni students yet. Students who pass Class Five will appear here.
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                    <th class="p-4">S#</th>
                    <th class="p-4">GR No</th>
                    <th class="p-4">Student Name</th>
                    <th class="p-4">Father Name</th>
                    <th class="p-4">Gender</th>
                    <th class="p-4">Admission Date</th>
                    <th class="p-4">Graduation Year</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $i = 1; foreach ($alumniStudents as $student): 
                    $graduationYear = isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : 'N/A';
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="p-4 text-gray-500 font-medium"><?php echo $i++; ?></td>
                    <td class="p-4 text-gray-700 font-medium"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                    <td class="p-4 text-gray-800 font-semibold">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($student['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover object-top">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">
                                    <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="capitalize"><?php echo htmlspecialchars($student['student_name']); ?></span>
                        </div>
                    </td>
                    <td class="p-4 text-gray-600 capitalize"><?php echo htmlspecialchars($student['father_name']); ?></td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $student['gender'] == 'Male' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'; ?>">
                            <?php echo htmlspecialchars($student['gender']); ?>
                        </span>
                    </td>
                    <td class="p-4 text-gray-500 text-sm"><?php echo htmlspecialchars($student['admission_date']); ?></td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <i class="fas fa-graduation-cap"></i> <?php echo $graduationYear; ?>
                            </span>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="text-blue-500 hover:text-blue-700 transition-colors" title="View Profile">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
