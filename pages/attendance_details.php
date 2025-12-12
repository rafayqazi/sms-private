<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$status = isset($_GET['status']) ? $_GET['status'] : 'Absent';
$statusMap = [
    'Present' => 'P',
    'Absent' => 'A',
    'Leave' => 'L'
];
$statusCode = isset($statusMap[$status]) ? $statusMap[$status] : 'A';

$db = new Database();
$result = $db->getStudentsByAttendanceStatus($statusCode);
$students = $result['students'];
$date = $result['date'];
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">Students - <?php echo htmlspecialchars($status); ?></h1>
        <p class="text-green-100 mt-1">Date: <?php echo htmlspecialchars($date); ?></p>
    </div>
    <a href="../index.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium w-full md:w-auto">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
</div>

<div class="bg-white shadow-lg rounded-lg p-6">
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                    <th class="p-4">GR No</th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Father Name</th>
                    <th class="p-4">Class</th>
                    <th class="p-4">Contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="p-4 text-gray-700"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                            <td class="p-4 font-medium text-gray-800 capitalize"><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td class="p-4 text-gray-600 capitalize"><?php echo htmlspecialchars($student['father_name']); ?></td>
                            <td class="p-4 text-gray-600"><?php echo htmlspecialchars($student['current_class']); ?></td>
                            <td class="p-4 text-gray-600"><?php echo htmlspecialchars(isset($student['father_contact']) ? $student['father_contact'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-info-circle text-4xl text-gray-300 mb-3"></i>
                                <p>No students found with status '<?php echo htmlspecialchars($status); ?>' for the latest date.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
