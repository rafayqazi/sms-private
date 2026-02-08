<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
$db = new Database();

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$class = isset($_GET['class']) ? $_GET['class'] : '';

$students = [];
$attendanceData = [];
$stats = ['P' => 0, 'A' => 0, 'L' => 0, 'Unmarked' => 0];

if ($class) {
    $students = $db->filterStudents(['class' => $class, 'sort_by' => 'gr_no', 'order' => 'ASC']);
    $attendanceData = $db->getAttendance($date, $class);

    // Calculate Stats
    foreach ($students as $student) {
        $status = isset($attendanceData[$student['id']]) ? $attendanceData[$student['id']] : '';
        if ($status == 'P') $stats['P']++;
        elseif ($status == 'A') $stats['A']++;
        elseif ($status == 'L') $stats['L']++;
        else $stats['Unmarked']++;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">Attendance Reports</h1>
        <p class="text-green-100 mt-1">View and print attendance records</p>
    </div>
    <a href="attendance.php" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium w-full md:w-auto">
        <i class="fas fa-calendar-check"></i> Mark Attendance
    </a>
</div>

<div class="bg-white shadow-lg rounded-lg p-6">
    <!-- Print Header -->
    <div class="hidden print:block text-center mb-6 border-b pb-4">
        <div class="flex flex-col items-center justify-center">
            <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="School Logo" class="w-24 h-24 object-contain mb-2">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($headerSettings['school_name'] ?? 'School Name'); ?></h2>
            <p class="text-gray-600">Attendance Report</p>
        </div>
    </div>

    <form class="flex flex-col md:flex-row flex-wrap gap-4 mb-6 items-end print:hidden">
        <div class="flex flex-col gap-2 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Date</label>
            <input type="date" id="dateInput" value="<?php echo $date; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
        </div>
        <div class="flex flex-col gap-2 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Class</label>
            <select id="classSelect" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
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
        <button type="button" onclick="window.print()" class="w-full md:w-auto px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 md:ml-auto flex items-center justify-center gap-2">
            <i class="fas fa-print"></i> Print
        </button>
    </form>

    <div id="loadingMessage" class="text-center text-gray-500 hidden py-4">
        <i class="fas fa-spinner fa-spin"></i> Loading report...
    </div>

    <div id="reportContainer" class="hidden">
        <!-- Summary Stats -->
        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-green-50 p-4 rounded-lg text-center border border-green-100">
                <h3 id="statP" class="text-green-600 text-2xl font-bold">0</h3>
                <span class="text-sm text-green-800 font-medium">Present</span>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center border border-red-100">
                <h3 id="statA" class="text-red-600 text-2xl font-bold">0</h3>
                <span class="text-sm text-red-800 font-medium">Absent</span>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg text-center border border-yellow-100">
                <h3 id="statL" class="text-yellow-600 text-2xl font-bold">0</h3>
                <span class="text-sm text-yellow-800 font-medium">Leave</span>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                <h3 id="statTotal" class="text-gray-600 text-2xl font-bold">0</h3>
                <span class="text-sm text-gray-500 font-medium">Total Students</span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="p-4">S#</th>
                        <th class="p-4">GR No</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Father's Name</th>
                        <th class="p-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody" class="divide-y divide-gray-100">
                    <!-- Rows populated by JS -->
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="noDataMessage" class="hidden bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.getElementById('classSelect');
            const dateInput = document.getElementById('dateInput');
            const reportContainer = document.getElementById('reportContainer');
            const loadingMessage = document.getElementById('loadingMessage');
            const noDataMessage = document.getElementById('noDataMessage');
            const tableBody = document.getElementById('reportTableBody');

            // Stats Elements
            const statP = document.getElementById('statP');
            const statA = document.getElementById('statA');
            const statL = document.getElementById('statL');
            const statTotal = document.getElementById('statTotal');

            function loadReport() {
                const className = classSelect.value;
                const date = dateInput.value;

                if (!className) {
                    reportContainer.classList.add('hidden');
                    noDataMessage.classList.add('hidden');
                    return;
                }

                loadingMessage.classList.remove('hidden');
                reportContainer.classList.add('hidden');
                noDataMessage.classList.add('hidden');

                fetch(`../api/get_attendance_report.php?class=${encodeURIComponent(className)}&date=${encodeURIComponent(date)}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingMessage.classList.add('hidden');

                        if (data.students && data.students.length > 0) {
                            // Update Stats
                            statP.textContent = data.stats.P;
                            statA.textContent = data.stats.A;
                            statL.textContent = data.stats.L;
                            statTotal.textContent = data.stats.Total;

                            // Update Table
                            tableBody.innerHTML = '';
                            data.students.forEach((student, index) => {
                                let statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800">Unmarked</span>';
                                if (student.status === 'P') statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">Present</span>';
                                else if (student.status === 'A') statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">Absent</span>';
                                else if (student.status === 'L') statusBadge = '<span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">Leave</span>';

                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td class="p-4 text-gray-500 font-medium">${index + 1}</td>
                                    <td class="p-4 text-gray-700">${student.gr_no}</td>
                                    <td class="p-4 font-medium text-gray-800 capitalize">${student.student_name}</td>
                                    <td class="p-4 text-gray-600 capitalize">${student.father_name}</td>
                                    <td class="p-4 text-center">${statusBadge}</td>
                                `;
                                tableBody.appendChild(row);
                            });

                            reportContainer.classList.remove('hidden');
                        } else {
                            noDataMessage.classList.remove('hidden');
                            showModal('warning', 'No Data', 'No students found in this class.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingMessage.classList.add('hidden');
                        showModal('error', 'Error', 'Failed to load report. Please try again.');
                    });
            }

            classSelect.addEventListener('change', loadReport);
            dateInput.addEventListener('change', loadReport);

            // Initial load
            if (!classSelect.value && classSelect.options.length > 1) {
                classSelect.selectedIndex = 1; // Select first available class
                loadReport();
            } else if (classSelect.value) {
                loadReport();
            }
        });
    </script>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        /* Ensure background colors print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
