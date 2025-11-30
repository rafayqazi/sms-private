<?php
require_once 'includes/auth_session.php';
require_once 'includes/db.php';
$db = new Database();

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$class = isset($_GET['class']) ? $_GET['class'] : '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $class = $_POST['class'];
    $attendanceData = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    
    if ($db->saveAttendance($date, $class, $attendanceData)) {
        $message = "Attendance saved successfully!";
    } else {
        $message = "Error saving attendance.";
    }
}

$students = [];
$existingAttendance = [];

if ($class) {
    $students = $db->filterStudents(['class' => $class, 'sort_by' => 'gr_no', 'order' => 'ASC']);
    $existingAttendance = $db->getAttendance($date, $class);
}
?>

<?php include 'includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold">Mark Attendance</h1>
        <p class="text-green-100 mt-1">Record daily student attendance</p>
    </div>
    <a href="attendance_view.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center gap-2 font-medium">
        <i class="fas fa-list-alt"></i> View Reports
    </a>
</div>

<div class="bg-white shadow-lg rounded-lg p-6">
    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>', 
                          '<?php echo strpos($message, 'Error') !== false ? 'Error' : 'Success'; ?>', 
                          '<?php echo addslashes($message); ?>');
            });
        </script>
    <?php endif; ?>

    <form id="attendanceForm" action="" method="POST">
        <div class="flex flex-wrap gap-6 mb-8 items-end">
            <div class="flex flex-col gap-2 min-w-[200px]">
                <label class="text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" id="dateInput" value="<?php echo $date; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div class="flex flex-col gap-2 min-w-[200px]">
                <label class="text-sm font-medium text-gray-700">Class</label>
                <select name="class" id="classSelect" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                    <option value="">Select Class</option>
                    <?php
                    $classes = ['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'];
                    foreach ($classes as $c) {
                        $selected = ($class == $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div id="studentsContainer" class="hidden">
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="p-4">GR No</th>
                            <th class="p-4">Name</th>
                            <th class="p-4">Father's Name</th>
                            <th class="p-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody" class="divide-y divide-gray-100">
                        <!-- Rows will be populated by JS -->
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-primary to-green-800 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:from-green-800 hover:to-green-900 transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-3 text-lg">
                    <i class="fas fa-save"></i> Save Attendance Record
                </button>
            </div>
        </div>
        <div id="noStudentsMessage" class="hidden bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        No students found in this class.
                    </p>
                </div>
            </div>
        </div>
        <div id="loadingMessage" class="text-center text-gray-500 hidden py-4">
            <i class="fas fa-spinner fa-spin"></i> Loading students...
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.getElementById('classSelect');
            const dateInput = document.getElementById('dateInput');
            const studentsContainer = document.getElementById('studentsContainer');
            const studentsTableBody = document.getElementById('studentsTableBody');
            const noStudentsMessage = document.getElementById('noStudentsMessage');
            const loadingMessage = document.getElementById('loadingMessage');

            function loadStudents() {
                const className = classSelect.value;
                const date = dateInput.value;

                if (!className) {
                    studentsContainer.classList.add('hidden');
                    noStudentsMessage.classList.add('hidden');
                    return;
                }

                // Show loading
                loadingMessage.classList.remove('hidden');
                studentsContainer.classList.add('hidden');
                noStudentsMessage.classList.add('hidden');

                fetch(`api/get_attendance_data.php?class=${encodeURIComponent(className)}&date=${encodeURIComponent(date)}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingMessage.classList.add('hidden');
                        studentsTableBody.innerHTML = '';

                        if (data.length > 0) {
                            data.forEach(student => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td class="p-4 font-medium text-gray-700">${student.gr_no}</td>
                                    <td class="p-4 font-semibold text-gray-800">${student.student_name}</td>
                                    <td class="p-4 text-gray-600">${student.father_name}</td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-6">
                                            <label class="cursor-pointer flex items-center gap-2 px-3 py-1 rounded-md hover:bg-green-50 transition-colors">
                                                <input type="radio" name="attendance[${student.id}]" value="P" ${student.status === 'P' || student.status === '' ? 'checked' : ''} class="w-4 h-4 text-green-600 focus:ring-green-500">
                                                <span class="text-green-700 font-bold text-sm">Present</span>
                                            </label>
                                            <label class="cursor-pointer flex items-center gap-2 px-3 py-1 rounded-md hover:bg-red-50 transition-colors">
                                                <input type="radio" name="attendance[${student.id}]" value="A" ${student.status === 'A' ? 'checked' : ''} class="w-4 h-4 text-red-600 focus:ring-red-500">
                                                <span class="text-red-700 font-bold text-sm">Absent</span>
                                            </label>
                                            <label class="cursor-pointer flex items-center gap-2 px-3 py-1 rounded-md hover:bg-yellow-50 transition-colors">
                                                <input type="radio" name="attendance[${student.id}]" value="L" ${student.status === 'L' ? 'checked' : ''} class="w-4 h-4 text-yellow-600 focus:ring-yellow-500">
                                                <span class="text-yellow-700 font-bold text-sm">Leave</span>
                                            </label>
                                        </div>
                                    </td>
                                `;
                                studentsTableBody.appendChild(row);
                            });
                            studentsContainer.classList.remove('hidden');
                        } else {
                            noStudentsMessage.classList.remove('hidden');
                            showModal('warning', 'No Students', 'No students found in this class.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingMessage.classList.add('hidden');
                        showModal('error', 'Error', 'Failed to load students. Please try again.');
                    });
            }

            // Event Listeners
            classSelect.addEventListener('change', loadStudents);
            dateInput.addEventListener('change', loadStudents);

            // Initial Load if class is selected (or select first class if not)
            if (!classSelect.value) {
                 classSelect.selectedIndex = 2; // Select 'One'
                 loadStudents();
            } else {
                loadStudents();
            }
        });
    </script>
</div>

<?php include 'includes/footer.php'; ?>
