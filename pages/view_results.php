<?php
require_once '../includes/auth_session.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('view_results.php')) {
    header("Location: ../index.php");
    exit;
}

$db = new Database();
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$selectedExam = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

if ($selectedClass && isEditor()) {
    $assigned = getAssignedClasses();
    if (!in_array($selectedClass, $assigned)) {
        die("Unauthorized access to this class.");
    }
}

$students = [];
$results = [];

if ($selectedClass && $selectedExam && $selectedYear) {
    $results = $db->getResults($selectedClass, $selectedExam, $selectedYear);
    $resultStudentIds = array_keys($results);
    
    // Fetch all students and filter to include those with results OR currently in class
    $allStudents = $db->readData();
    $students = array_filter($allStudents, function($student) use ($selectedClass, $resultStudentIds) {
        $isCurrent = ($student['current_class'] === $selectedClass && 
                     (!isset($student['student_status']) || $student['student_status'] !== 'Alumni'));
        $hasResult = in_array($student['id'], $resultStudentIds);
        return $isCurrent || $hasResult;
    });
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">View Results</h1>
        <p class="text-blue-100 mt-1">View and print student result cards</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <form id="resultFilterForm" method="GET" class="flex flex-col md:flex-row flex-wrap gap-4 items-end">
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Class</label>
            <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">Select Class</option>
                <?php foreach (getAssignedClasses() as $c): ?>
                    <option value="<?= $c ?>" <?= $selectedClass == $c ? 'selected' : '' ?>>Class <?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Exam Type</label>
            <select name="exam_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">Select Exam</option>
                <option value="Annual" <?= $selectedExam == 'Annual' ? 'selected' : '' ?>>Annual</option>
                <option value="Mid Term" <?= $selectedExam == 'Mid Term' ? 'selected' : '' ?>>Mid Term</option>
            </select>
        </div>

        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[150px]">
            <label class="text-sm font-medium text-gray-700">Year</label>
            <input type="number" name="year" id="yearInput" value="<?= $selectedYear ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <div class="flex flex-col gap-1 w-full md:w-auto">
             <button type="submit" class="h-[42px] bg-blue-600 text-white px-6 rounded-md hover:bg-blue-700 transition duration-200 flex items-center justify-center whitespace-nowrap shadow-md">
                <i class="fas fa-search mr-2"></i> Search Results
            </button>
        </div>

        <div class="flex flex-col gap-1 w-full md:w-auto">
             <button type="button" onclick="printAllResults()" class="<?= ($selectedClass && $selectedExam && $selectedYear && !empty($students)) ? '' : 'hidden' ?> h-[42px] bg-indigo-100 text-indigo-700 border border-indigo-200 px-4 rounded-md hover:bg-indigo-200 transition duration-200 flex items-center justify-center whitespace-nowrap">
                <i class="fas fa-print mr-2"></i> Print All Marksheets
            </button>
        </div>
    </form>
</div>



<?php if ($selectedClass && $selectedExam && $selectedYear): ?>
    <?php if (!empty($students)): 
        // --- STATISTICS CALCULATION ---
        $stats = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'male_total' => 0,
            'male_passed' => 0,
            'male_failed' => 0,
            'female_total' => 0,
            'female_passed' => 0,
            'female_failed' => 0
        ];

        $studentScores = []; // For top 3

        foreach ($students as $student) {
            $stats['total']++;
            $stId = $student['id'];
            $gender = $student['gender']; // Assumes 'Male', 'Female'

            $isPassed = false;
            $totalMarks = 0;

            if (isset($results[$stId])) {
                $res = $results[$stId];
                $totalMarks = $res['total_obtained'];
                // Check pass/fail based on grade or remarks. 
                // Assuming 'F' grade is fail.
                if ($res['grade'] !== 'F') {
                    $isPassed = true;
                }
            }
            
            // Gender Stats
            if ($gender === 'Male') {
                $stats['male_total']++;
                if ($isPassed) $stats['male_passed']++; else $stats['male_failed']++;
            } elseif ($gender === 'Female') {
                $stats['female_total']++;
                if ($isPassed) $stats['female_passed']++; else $stats['female_failed']++;
            } else {
                // Fallback if gender is empty or other
                if ($isPassed) $stats['passed']++; // Will rely on sum of gender specific later or just use this for total
            }

            // Overall Stats
            if ($isPassed) $stats['passed']++; else $stats['failed']++;

            // Collect for Top 3 (only if passed or consider all?) - Usually top positions are for passed students
            if ($isPassed) {
                $studentScores[] = [
                    'name' => $student['student_name'],
                    'gender' => $gender,
                    'marks' => $totalMarks,
                    'percentage' => isset($results[$stId]) ? $results[$stId]['percentage'] : 0,
                    'image' => $student['profile_image']
                ];
            }
        }

        // Sort for Top 3
        usort($studentScores, function($a, $b) {
            return $b['marks'] - $a['marks'];
        });
        $top3 = array_slice($studentScores, 0, 3);
    ?>
    
    <!-- DASHBOARD STATS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- CHART 1: Overall Pass/Fail -->
        <div class="bg-white rounded-lg shadow-lg p-4 flex flex-col items-center">
            <h3 class="text-lg font-bold text-gray-700 mb-2">Overall Result Status</h3>
            <div class="w-full h-64 relative">
                <canvas id="overallChart"></canvas>
            </div>
            <div class="mt-4 text-sm text-gray-600 font-semibold">
                Total Students: <?= $stats['total'] ?> | Passed: <?= $stats['passed'] ?> | Failed: <?= $stats['failed'] ?>
            </div>
        </div>

        <!-- CHART 2: Male vs Female Comparison -->
        <div class="bg-white rounded-lg shadow-lg p-4 flex flex-col items-center">
            <h3 class="text-lg font-bold text-gray-700 mb-2">Gender-wise Performance</h3>
            <div class="w-full h-64 relative">
                <canvas id="genderChart"></canvas>
            </div>
             <div class="mt-4 text-xs text-gray-500 text-center">
                M: <?= $stats['male_total'] ?> (P:<?= $stats['male_passed'] ?>/F:<?= $stats['male_failed'] ?>) | 
                F: <?= $stats['female_total'] ?> (P:<?= $stats['female_passed'] ?>/F:<?= $stats['female_failed'] ?>)
            </div>
        </div>

        <!-- LIST: Top 3 Position Holders -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-lg p-4 text-white overflow-hidden relative">
            <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-20">
                <i class="fas fa-trophy text-9xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-4 border-b border-white/30 pb-2 relative z-10"><i class="fas fa-crown text-yellow-300 mr-2"></i>Top Position Holders</h3>
            
            <div class="space-y-4 relative z-10">
                <?php if (!empty($top3)): ?>
                    <?php foreach ($top3 as $index => $winner): 
                        $medalColor = match($index) {
                            0 => 'text-yellow-300',
                            1 => 'text-gray-300',
                            2 => 'text-orange-300',
                            default => 'text-white'
                        };
                        $suffix = match($index) {
                            0 => 'st',
                            1 => 'nd',
                            2 => 'rd',
                            default => 'th'
                        };
                    ?>
                    <div class="flex items-center bg-white/10 p-2 rounded-lg backdrop-blur-sm">
                        <div class="font-bold text-2xl w-10 text-center <?= $medalColor ?> italic">
                            <?= ($index + 1) ?><sup><?= $suffix ?></sup>
                        </div>
                        <div class="h-12 w-12 rounded-full border-2 border-white/50 overflow-hidden mx-3 bg-gray-200 shrink-0">
                             <?php if ($winner['image']): ?>
                                <img src="<?= $winner['image'] ?>" class="h-full w-full object-cover object-top">
                            <?php else: ?>
                                <i class="fas fa-user text-gray-400 h-full w-full flex items-center justify-center"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold truncate text-lg leading-tight"><?= htmlspecialchars($winner['name']) ?></div>
                            <div class="text-xs text-indigo-100"><?= $winner['gender'] ?> | <?= $winner['percentage'] ?>%</div>
                        </div>
                        <div class="font-bold text-xl ml-2">
                            <?= $winner['marks'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center mt-10 opacity-70">No results found yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data from PHP
        const stats = <?= json_encode($stats) ?>;

        // 1. Overall Pie Chart
        const ctxOverall = document.getElementById('overallChart').getContext('2d');
        new Chart(ctxOverall, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{
                    data: [stats.passed, stats.failed],
                    backgroundColor: ['#22c55e', '#ef4444'], // Green-500, Red-500
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // 2. Gender Bar Chart
        const ctxGender = document.getElementById('genderChart').getContext('2d');
        new Chart(ctxGender, {
            type: 'bar',
            data: {
                labels: ['Male', 'Female'],
                datasets: [
                    {
                        label: 'Passed',
                        data: [stats.male_passed, stats.female_passed],
                        backgroundColor: '#3b82f6' // Blue-500
                    },
                    {
                        label: 'Failed',
                        data: [stats.male_failed, stats.female_failed],
                        backgroundColor: '#ef4444' // Red-500
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
    <!-- END CHARTS SECTION -->

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <!-- Table Header and Body remains same, just ensuring we are inside the 'if !empty students' block -->
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">GR No</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Father Name</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Obtained</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Percentage</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Grade</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($students as $student): 
                            $studentId = $student['id'];
                            $result = isset($results[$studentId]) ? $results[$studentId] : null;
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium"><?= htmlspecialchars($student['gr_no']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize"><?= htmlspecialchars($student['student_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize"><?= htmlspecialchars($student['father_name']) ?></td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-700">
                                <?= $result ? $result['total_obtained'] : '<span class="text-gray-400">0</span>' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                <?= $result ? $result['percentage'] . '%' : '<span class="text-gray-400">0%</span>' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <?php if ($result): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $result['grade'] == 'F' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= $result['grade'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">F</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick="printResult(<?= $studentId ?>)" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md transition-colors mr-2">
                                    <i class="fas fa-print mr-1"></i> Print Card
                                </button>
                                <?php 
                                    $phone = isset($student['father_contact']) ? $student['father_contact'] : '';
                                    if ($result) {
                                        $msg = "Assalam-o-Alaikum, Result of *" . $student['student_name'] . "*\n";
                                        $msg .= "Class: " . $student['current_class'] . "\n";
                                        $msg .= "Exam: " . $selectedExam . " (" . $selectedYear . ")\n";
                                        $msg .= "Obtained: " . $result['total_obtained'] . "/" . $result['total_max'] . "\n";
                                        $msg .= "Percentage: " . $result['percentage'] . "%\n";
                                        $msg .= "Grade: " . $result['grade'];
                                        // Encode for JS
                                        $encodedMsg = htmlspecialchars(json_encode($msg), ENT_QUOTES, 'UTF-8');
                                        $encodedPhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
                                    } else {
                                        $encodedMsg = "''";
                                        $encodedPhone = "''";
                                    }
                                ?>
                                <?php if ($result && $phone): ?>
                                <button onclick='shareWhatsapp(<?= $encodedPhone ?>, <?= $encodedMsg ?>)' class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 px-3 py-1 rounded-md transition-colors">
                                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-md p-12 text-center text-gray-500 border border-gray-100">
            <i class="fas fa-user-graduate text-4xl mb-4 text-gray-300"></i>
            <p class="text-lg">No students found in this class.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Teacher Name Modal -->
<div id="teacherModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" onclick="closeModalBackdrop(event)">
    <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white" onclick="event.stopPropagation()">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Print Results</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 mb-3">
                    Please enter the Class Teacher's Name to appear on the marksheets.
                </p>
                <input type="text" id="modalTeacherName" placeholder="Class Teacher Name (e.g. Sir Ahmed)" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                       onkeypress="handleEnter(event)">
            </div>
            <div class="items-center px-4 py-3">
                <button type="button" id="okBtn" onclick="confirmPrint()" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 mb-2">
                    Print Marksheets
                </button>
                <button type="button" id="cancelBtn" onclick="closeTeacherModal()" class="px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function printResult(studentId) {
    window.open('print_result.php?id=' + studentId + '&exam_type=<?= $selectedExam ?>&year=<?= $selectedYear ?>', '_blank');
}

function shareWhatsapp(phone, message) {
    let cleanPhone = phone.toString().replace(/\D/g, '');
    if (cleanPhone.startsWith('03')) {
        cleanPhone = '92' + cleanPhone.substring(1);
    }
    const url = `https://web.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
}

function handleEnter(e) {
    if (e.key === 'Enter') confirmPrint();
}

function printAllResults() {
    const classVal = document.querySelector('select[name="class"]').value;
    const examVal = document.querySelector('select[name="exam_type"]').value;
    const yearVal = document.getElementById('yearInput').value;
    
    if (classVal && examVal && yearVal) {
        document.getElementById('teacherModal').classList.remove('hidden');
        document.getElementById('modalTeacherName').focus();
    } else {
        alert('Please select Class, Exam and Year first.');
    }
}

function closeModalBackdrop(event) {
    if (event.target.id === 'teacherModal') {
        closeTeacherModal();
    }
}

function closeTeacherModal() {
    document.getElementById('teacherModal').classList.add('hidden');
    document.getElementById('modalTeacherName').value = ''; 
}

function confirmPrint() {
    const classVal = document.querySelector('select[name="class"]').value;
    const examVal = document.querySelector('select[name="exam_type"]').value;
    const yearVal = document.getElementById('yearInput').value;
    const teacherName = document.getElementById('modalTeacherName').value;
    
    // Close modal
    closeTeacherModal();
    
    // Open print page
    window.open(`print_all_results.php?class=${encodeURIComponent(classVal)}&exam_type=${encodeURIComponent(examVal)}&year=${encodeURIComponent(yearVal)}&teacher_name=${encodeURIComponent(teacherName)}`, '_blank');
}

// Real-time loading removed as per user request
</script>

<?php include '../includes/footer.php'; ?>
