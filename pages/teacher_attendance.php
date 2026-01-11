<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
$db = new Database();

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $attendanceData = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    
    if ($db->saveTeacherAttendance($date, $attendanceData)) {
        $message = "Teacher attendance saved successfully!";
    } else {
        $message = "Error saving teacher attendance.";
    }
}

$teachers = $db->getAllTeachers();
$existingAttendance = $db->getTeacherAttendance($date);
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-indigo-600 to-indigo-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl md:text-3xl font-bold">Teacher Attendance</h1>
        <p class="text-indigo-100 mt-1">Record daily attendance for school staff</p>
    </div>
    <a href="teacher_attendance_view.php" class="w-full md:w-auto bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium">
        <i class="fas fa-list-alt"></i> Attendance Reports
    </a>
</div>

<div class="bg-white shadow-lg rounded-lg p-6">
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'Error') !== false ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'; ?> flex items-center gap-3">
            <i class="fas <?php echo strpos($message, 'Error') !== false ? 'fa-times-circle' : 'fa-check-circle'; ?> text-xl"></i>
            <p class="font-medium"><?php echo $message; ?></p>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="flex flex-col md:flex-row gap-6 mb-8 items-end">
            <div class="flex flex-col gap-2 min-w-[200px]">
                <label class="text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required onchange="window.location.href='?date=' + this.value">
            </div>
            <div class="flex-grow"></div>
            <div class="flex gap-2">
                <button type="button" onclick="markAll('P')" class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded border border-green-200 hover:bg-green-100 transition-colors">Mark All Present</button>
                <button type="button" onclick="markAll('A')" class="text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded border border-red-200 hover:bg-red-100 transition-colors">Mark All Absent</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="p-4">Teacher Info</th>
                        <th class="p-4">Designation</th>
                        <th class="p-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="3" class="p-8 text-center text-gray-500">No teachers found in the system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $teacher): ?>
                            <?php 
                            $tid = $teacher['id'];
                            $status = isset($existingAttendance[$tid]) ? $existingAttendance[$tid] : 'P';
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold overflow-hidden shadow-sm">
                                            <?php if (!empty($teacher['profile_image'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($teacher['profile_image']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></div>
                                            <div class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($tid); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-600 text-sm italic">
                                    <?php echo htmlspecialchars($teacher['designation']); ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-center gap-2">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendance[<?php echo $tid; ?>]" value="P" <?php echo ($status == 'P') ? 'checked' : ''; ?> class="peer hidden attendance-radio-p">
                                            <div class="px-4 py-2 rounded-lg text-sm font-bold border border-gray-200 text-gray-400 peer-checked:bg-green-500 peer-checked:text-white peer-checked:border-green-600 transition-all hover:bg-gray-50 group">
                                                Present
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendance[<?php echo $tid; ?>]" value="A" <?php echo ($status == 'A') ? 'checked' : ''; ?> class="peer hidden attendance-radio-a">
                                            <div class="px-4 py-2 rounded-lg text-sm font-bold border border-gray-200 text-gray-400 peer-checked:bg-red-500 peer-checked:text-white peer-checked:border-red-600 transition-all hover:bg-gray-50">
                                                Absent
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendance[<?php echo $tid; ?>]" value="L" <?php echo ($status == 'L') ? 'checked' : ''; ?> class="peer hidden attendance-radio-l">
                                            <div class="px-4 py-2 rounded-lg text-sm font-bold border border-gray-200 text-gray-400 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-600 transition-all hover:bg-gray-50">
                                                Leave
                                            </div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-indigo-200 transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-3">
                <i class="fas fa-check-double"></i> Save Attendance
            </button>
        </div>
    </form>
</div>

<script>
function markAll(status) {
    const radios = document.querySelectorAll(`input[value="${status}"]`);
    radios.forEach(radio => radio.checked = true);
}
</script>

<?php include '../includes/footer.php'; ?>
