<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
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

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl md:text-3xl font-bold">Mark Attendance</h1>
        <p class="text-green-100 mt-1">Record daily student attendance</p>
    </div>
    <a href="attendance_view.php" class="w-full md:w-auto bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium">
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
        <div class="flex flex-col md:flex-row flex-wrap gap-6 mb-8 items-end">
            <div class="flex flex-col gap-2 min-w-[200px]">
                <label class="text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" id="dateInput" value="<?php echo $date; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div class="flex flex-col gap-2 min-w-[200px]">
                <label class="text-sm font-medium text-gray-700">Class</label>
                <select name="class" id="classSelect" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                    <option value="">Select Class</option>
                    <?php
                    $classes = getAssignedClasses(); // Get classes based on user role
                    foreach ($classes as $c) {
                        $selected = ($class == $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div id="studentsContainer" class="hidden">
            <!-- Desktop Headers -->
            <div class="hidden md:grid md:grid-cols-12 gap-4 bg-gray-50 border-b border-gray-200 px-6 py-3 text-xs uppercase tracking-wider text-gray-500 font-semibold rounded-t-lg">
                <div class="col-span-1">GR No</div>
                <div class="col-span-3">Name</div>
                <div class="col-span-3">Father's Name</div>
                <div class="col-span-5 text-center">Status</div>
            </div>

            <!-- Student List Container -->
            <div id="studentsList" class="space-y-4 md:space-y-0 md:bg-white md:border-x md:border-b md:border-gray-200 md:rounded-b-lg">
                <!-- Items will be populated by JS -->
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

                fetch(`../api/get_attendance_data.php?class=${encodeURIComponent(className)}&date=${encodeURIComponent(date)}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingMessage.classList.add('hidden');
                        const studentsList = document.getElementById('studentsList');
                        studentsList.innerHTML = '';

                        if (data.length > 0) {
                            data.forEach(student => {
                                const row = document.createElement('div');
                                row.className = 'bg-white p-4 rounded-lg shadow-sm border border-gray-100 md:shadow-none md:border-0 md:border-b md:border-gray-100 md:rounded-none md:grid md:grid-cols-12 md:gap-4 md:items-center hover:bg-gray-50 transition-colors';
                                
                                // Status Logic for checked state
                                const isPresent = student.status === 'P' || student.status === '';
                                const isAbsent = student.status === 'A';
                                const isLeave = student.status === 'L';

                                row.innerHTML = `
                                    <!-- Mobile: Header Info -->
                                    <div class="flex items-center justify-between mb-4 md:hidden border-b border-gray-100 pb-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                                ${student.student_name.charAt(0).toUpperCase()}
                                            </div>
                                            <span class="font-bold text-gray-800">${student.student_name}</span>
                                        </div>
                                        <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-600">GR: ${student.gr_no}</span>
                                    </div>

                                    <!-- Desktop Columns / Mobile Data -->
                                    <div class="hidden md:block col-span-1 text-gray-600 font-mono text-sm">${student.gr_no}</div>
                                    <div class="hidden md:block col-span-3 font-medium text-gray-900 capitalize flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                            ${student.student_name.charAt(0).toUpperCase()}
                                        </div>
                                        ${student.student_name}
                                    </div>
                                    <div class="hidden md:block col-span-3 text-gray-600 capitalize text-sm">${student.father_name}</div>
                                    
                                    <!-- Mobile: Father Name -->
                                    <div class="md:hidden mb-4 text-sm text-gray-600">
                                        <span class="text-gray-400 text-xs uppercase tracking-wide">Father:</span> ${student.father_name}
                                    </div>

                                    <!-- Status Radios -->
                                    <div class="col-span-5">
                                        <div class="flex flex-row justify-between md:justify-center gap-2 md:gap-4 bg-gray-50 md:bg-transparent p-2 md:p-0 rounded-lg">
                                            <label class="cursor-pointer flex-1 md:flex-none">
                                                <input type="radio" name="attendance[${student.id}]" value="P" ${isPresent ? 'checked' : ''} class="peer hidden">
                                                <div class="text-center py-2 px-3 rounded-md text-sm font-medium transition-all duration-200
                                                    text-gray-500 hover:bg-white
                                                    peer-checked:bg-green-100 peer-checked:text-green-700 peer-checked:shadow-sm ring-1 ring-transparent peer-checked:ring-green-200">
                                                    Present
                                                </div>
                                            </label>
                                            
                                            <label class="cursor-pointer flex-1 md:flex-none">
                                                <input type="radio" name="attendance[${student.id}]" value="A" ${isAbsent ? 'checked' : ''} class="peer hidden">
                                                <div class="text-center py-2 px-3 rounded-md text-sm font-medium transition-all duration-200
                                                    text-gray-500 hover:bg-white
                                                    peer-checked:bg-red-100 peer-checked:text-red-700 peer-checked:shadow-sm ring-1 ring-transparent peer-checked:ring-red-200">
                                                    Absent
                                                </div>
                                            </label>
                                            
                                            <label class="cursor-pointer flex-1 md:flex-none">
                                                <input type="radio" name="attendance[${student.id}]" value="L" ${isLeave ? 'checked' : ''} class="peer hidden">
                                                <div class="text-center py-2 px-3 rounded-md text-sm font-medium transition-all duration-200
                                                    text-gray-500 hover:bg-white
                                                    peer-checked:bg-yellow-100 peer-checked:text-yellow-700 peer-checked:shadow-sm ring-1 ring-transparent peer-checked:ring-yellow-200">
                                                    Leave
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                `;
                                studentsList.appendChild(row);
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

<?php include '../includes/footer.php'; ?>
