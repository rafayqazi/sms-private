<?php
require_once '../includes/auth_session.php';
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
    <form method="GET" class="flex flex-col md:flex-row flex-wrap gap-4 items-end">
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Class</label>
            <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">Select Class</option>
                <option value="Kachi" <?= $selectedClass == 'Kachi' ? 'selected' : '' ?>>Class Kachi</option>
                <option value="One" <?= $selectedClass == 'One' ? 'selected' : '' ?>>Class One</option>
                <option value="Two" <?= $selectedClass == 'Two' ? 'selected' : '' ?>>Class Two</option>
                <option value="Three" <?= $selectedClass == 'Three' ? 'selected' : '' ?>>Class Three</option>
                <option value="Four" <?= $selectedClass == 'Four' ? 'selected' : '' ?>>Class Four</option>
                <option value="Five" <?= $selectedClass == 'Five' ? 'selected' : '' ?>>Class Five</option>
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
             <button type="button" onclick="printAllResults()" class="<?= ($selectedClass && $selectedExam && $selectedYear && !empty($students)) ? '' : 'hidden' ?> h-[42px] bg-indigo-100 text-indigo-700 border border-indigo-200 px-4 rounded-md hover:bg-indigo-200 transition duration-200 flex items-center justify-center whitespace-nowrap">
                <i class="fas fa-print mr-2"></i> Print All Marksheets
            </button>
        </div>
    </form>
</div>

<script>
function printAllResults() {
    const classVal = document.querySelector('select[name="class"]').value;
    const examVal = document.querySelector('select[name="exam_type"]').value;
    const yearVal = document.getElementById('yearInput').value;
    
    if (classVal && examVal && yearVal) {
        window.open(`print_all_results.php?class=${encodeURIComponent(classVal)}&exam_type=${encodeURIComponent(examVal)}&year=${encodeURIComponent(yearVal)}`, '_blank');
    } else {
        alert('Please select Class, Exam and Year first.');
    }
}
</script>

<?php if ($selectedClass && $selectedExam && $selectedYear): ?>
    <?php if (!empty($students)): ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
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
                                <button onclick="printResult(<?= $studentId ?>)" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md transition-colors">
                                    <i class="fas fa-print mr-1"></i> Print Card
                                </button>
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

<script>
function printResult(studentId) {
    window.open('print_result.php?id=' + studentId + '&exam_type=<?= $selectedExam ?>&year=<?= $selectedYear ?>', '_blank');
}

// Real-time loading
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const selects = form.querySelectorAll('select');
    const yearInput = document.getElementById('yearInput');

    function checkAndSubmit() {
        let allFilled = true;
        selects.forEach(select => {
            if (!select.value) allFilled = false;
        });
        if (!yearInput.value) allFilled = false;

        if (allFilled) {
            form.submit();
        }
    }

    selects.forEach(select => {
        select.addEventListener('change', checkAndSubmit);
    });
    
    // For year input, maybe wait a bit or trigger on blur/enter? 
    // 'input' event triggers on every keystroke, which might be too aggressive for a submit.
    // Let's use 'change' which fires on enter or blur.
    yearInput.addEventListener('change', checkAndSubmit);
});
</script>

<?php include '../includes/footer.php'; ?>
