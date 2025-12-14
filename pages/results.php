<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('results.php')) {
    header("Location: ../index.php");
    exit;
}

$db = new Database();
$message = '';
$error = '';

$selectedClass = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$selectedExam = isset($_REQUEST['exam_type']) ? $_REQUEST['exam_type'] : '';
$selectedYear = isset($_REQUEST['year']) ? $_REQUEST['year'] : date('Y');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_results'])) {
        $results = $_POST['results'];
        $class = $_POST['class'];
        $examType = $_POST['exam_type'];
        $year = $_POST['year'];
        
        $count = 0;
        foreach ($results as $studentId => $marks) {
            // Check if any mark is entered
            $hasMarks = false;
            foreach ($marks as $mark) {
                if ($mark !== '') {
                    $hasMarks = true;
                    break;
                }
            }
            
            if ($hasMarks) {
                // Collect extra subjects
                $extra = isset($marks['extra']) ? $marks['extra'] : [];
                $jsonExtra = json_encode($extra);
                
                $resultData = [
                    'student_id' => $studentId,
                    'class' => $class,
                    'exam_type' => $examType,
                    'year' => $year,
                    'english' => (int)$marks['english'],
                    'math' => (int)$marks['math'],
                    'social_studies' => (int)$marks['social_studies'],
                    'general_science' => (int)$marks['general_science'],
                    'mt' => (int)$marks['mt'],
                    'islamiyat' => (int)$marks['islamiyat'],
                    'nmt' => (int)$marks['nmt'],
                    'other_subjects' => $jsonExtra
                ];
                $db->addResult($resultData);
                $count++;
            }
        }
        $message = "Results saved for $count students.";
    }
}

// Handle Add Subject
if (isset($_POST['add_subject'])) {
    $class = $_POST['class'];
    $examType = $_POST['exam_type'];
    $year = $_POST['year'];
    $newSubject = trim($_POST['new_subject_name']);

    if ($class && $examType && $year && $newSubject) {
        if ($db->addSubjectConfig($class, $examType, $year, $newSubject)) {
            $message = "Subject '$newSubject' added successfully.";
        } else {
            $error = "Subject already exists or could not be added.";
        }
    }
}

// Handle Delete Subject
if (isset($_POST['delete_subject'])) {
    $class = $_POST['class'];
    $examType = $_POST['exam_type'];
    $year = $_POST['year'];
    $subjectToDelete = $_POST['delete_subject']; 

    if ($class && $examType && $year && $subjectToDelete) {
        if ($db->deleteSubjectConfig($class, $examType, $year, $subjectToDelete)) {
            $message = "Subject '$subjectToDelete' deleted successfully.";
        } else {
            $error = "Failed to delete subject.";
        }
    }
}

// Handle Delete Result
if (isset($_POST['delete_result'])) {
    $resultId = $_POST['delete_result'];
    if ($db->deleteResult($resultId)) {
        $message = "Result deleted successfully.";
    } else {
        $error = "Failed to delete result.";
    }
}

// Handle Reset Results
if (isset($_POST['reset_results'])) {
    $class = $_POST['class'];
    $examType = $_POST['exam_type'];
    $year = $_POST['year'];

    if ($class && $examType && $year) {
        $count = $db->resetClassResults($class, $examType, $year);
        if ($count > 0) {
            $message = "Results reset for $count students.";
        } else {
            $message = "No results found to reset.";
        }
    }
}

$students = [];
$existingResults = [];
$extraSubjects = [];

if ($selectedClass && $selectedExam && $selectedYear) {
    $students = $db->getStudentsByClass($selectedClass);
    $existingResults = $db->getResults($selectedClass, $selectedExam, $selectedYear);
    $extraSubjects = $db->getSubjectConfig($selectedClass, $selectedExam, $selectedYear);
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6">
    <h1 class="text-3xl font-bold">Manage Results</h1>
    <p class="text-green-100 mt-1">Enter and view student results</p>
</div>

<?php if ($message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?= $message ?></p>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?= $error ?></p>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <form method="GET" class="flex flex-col md:flex-row flex-wrap gap-4 items-end" id="filterForm">
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Class</label>
            <select name="class" id="classSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="">Select Class</option>
                <?php foreach (['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'] as $c): ?>
                    <option value="<?= $c ?>" <?= $selectedClass === $c ? 'selected' : '' ?>>Class <?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Exam Type</label>
            <select name="exam_type" id="examSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="">Select Exam</option>
                <option value="Mid Term" <?= $selectedExam === 'Mid Term' ? 'selected' : '' ?>>Mid Term</option>
                <option value="Annual" <?= $selectedExam === 'Annual' ? 'selected' : '' ?>>Annual</option>
            </select>
        </div>
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Year</label>
            <input type="number" name="year" id="yearSelect" value="<?= $selectedYear ?: date('Y') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
        </div>
        <!-- Load Students Button Removed -->
        
        <div class="flex flex-col gap-1 w-full md:w-auto ml-auto flex-row items-end">
            <!-- Reset Button Triggers Modal -->
            <button type="button" onclick="document.getElementById('resetConfirmModal').classList.remove('hidden')" class="<?= ($selectedClass && $selectedExam && $selectedYear && !empty($existingResults)) ? '' : 'hidden' ?> bg-red-100 text-red-700 border border-red-200 px-4 py-2 rounded-md hover:bg-red-200 transition duration-200 flex items-center mr-2">
                <i class="fas fa-undo mr-2"></i> Reset Result
            </button>

             <button type="button" onclick="document.getElementById('addSubjectModal').classList.remove('hidden')" class="<?= ($selectedClass && $selectedExam && $selectedYear) ? '' : 'hidden' ?> bg-indigo-100 text-indigo-700 border border-indigo-200 px-4 py-2 rounded-md hover:bg-indigo-200 transition duration-200 flex items-center">
                <i class="fas fa-plus mr-2"></i> Add Subject
            </button>
        </div>
    </form>
</div>

<!-- Reset Confirmation Modal -->
<div id="resetConfirmModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('resetConfirmModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Reset Results</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to RESET all results for <strong><?= $selectedClass ?></strong>, <strong><?= $selectedExam ?></strong>, <strong><?= $selectedYear ?></strong> to 0? <br><br>
                                <span class="text-red-600 font-bold">This action cannot be undone!</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form method="POST">
                    <input type="hidden" name="class" value="<?= $selectedClass ?>">
                    <input type="hidden" name="exam_type" value="<?= $selectedExam ?>">
                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                    <button type="submit" name="reset_results" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Yes, Reset
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('resetConfirmModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div id="addSubjectModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('addSubjectModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Add New Subject</h3>
                    <input type="hidden" name="class" id="modalClass" value="<?= $selectedClass ?>">
                    <input type="hidden" name="exam_type" id="modalExam" value="<?= $selectedExam ?>">
                    <input type="hidden" name="year" id="modalYear" value="<?= $selectedYear ?>">
                    
                    <div class="mb-4">
                        <label for="new_subject_name" class="block text-sm font-medium text-gray-700 mb-1">Subject Name</label>
                        <input type="text" name="new_subject_name" id="new_subject_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., Computer, Drawing">
                    </div>
                    
                    <?php if (!empty($extraSubjects)): ?>
                    <div class="mt-6 border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Current Extra Subjects</h4>
                        <div class="space-y-2">
                            <?php foreach ($extraSubjects as $subj): ?>
                            <div class="flex justify-between items-center bg-gray-50 p-2 rounded border border-gray-200">
                                <span class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($subj) ?></span>
                                <button type="submit" name="delete_subject" value="<?= htmlspecialchars($subj) ?>" onclick="this.form.querySelector('[id=new_subject_name]').removeAttribute('required'); return confirm('Are you sure you want to delete this subject?');" class="text-red-600 hover:text-red-800 text-xs font-semibold px-2 py-1 rounded hover:bg-red-50 transition-colors">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="add_subject" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Add Subject
                    </button>
                    <button type="button" onclick="document.getElementById('addSubjectModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($students)): ?>
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Save Results Form -->
    <form method="POST">
        <input type="hidden" name="class" id="hiddenClass" value="<?= $selectedClass ?>">
        <input type="hidden" name="exam_type" id="hiddenExam" value="<?= $selectedExam ?>">
        <input type="hidden" name="year" id="hiddenYear" value="<?= $selectedYear ?>">
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 w-20">GR NO</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-20 bg-gray-50 z-10 w-40">NAME</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">ENG</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">MATH</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">S.ST</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">G.SCI</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">MT</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">ISL</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">NMT</th>
                        <?php foreach ($extraSubjects as $subj): ?>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"><?= strtoupper($subj) ?></th>
                        <?php endforeach; ?>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">TOTAL</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">GRADE</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">ACTION</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($students as $student): 
                        $result = isset($existingResults[$student['id']]) ? $existingResults[$student['id']] : null;
                        $otherSubjects = ($result && isset($result['other_subjects'])) ? json_decode($result['other_subjects'], true) : [];
                    ?>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white z-10"><?= $student['gr_no'] ?></td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 sticky left-20 bg-white z-10"><?= $student['student_name'] ?></td>
                        
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][english]" value="<?= $result ? $result['english'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][math]" value="<?= $result ? $result['math'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][social_studies]" value="<?= $result ? $result['social_studies'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][general_science]" value="<?= $result ? $result['general_science'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][mt]" value="<?= $result ? $result['mt'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][islamiyat]" value="<?= $result ? $result['islamiyat'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        <td class="px-2 py-2"><input type="number" name="results[<?= $student['id'] ?>][nmt]" value="<?= $result ? $result['nmt'] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500"></td>
                        
                        <?php foreach ($extraSubjects as $subj): ?>
                        <td class="px-2 py-2">
                            <input type="number" name="results[<?= $student['id'] ?>][other_subjects][<?= htmlspecialchars($subj) ?>]" value="<?= isset($otherSubjects[$subj]) ? $otherSubjects[$subj] : '' ?>" class="mark-input w-full px-2 py-1 border rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </td>
                        <?php endforeach; ?>
                        
                        <td class="px-2 py-2 font-bold text-center total-marks text-gray-700 bg-gray-50 border rounded-md min-w-[3rem] p-1">
                            <?= $result ? $result['total_obtained'] : '0' ?>
                        </td>
                        <td class="px-2 py-2 text-center grade-cell">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= ($result && $result['grade'] == 'F') ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                <?= $result ? $result['grade'] : 'F' ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2 justify-center">
                                <a href="print_result.php?student_id=<?= $student['id'] ?>&class=<?= $selectedClass ?>&exam_type=<?= $selectedExam ?>&year=<?= $selectedYear ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded hover:bg-indigo-100 transition-colors" title="Print Result">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="fixed bottom-0 right-0 p-4 bg-white border-t border-gray-200 w-full flex justify-end shadow-lg z-20">
            <button type="submit" name="save_results" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md flex items-center">
                <i class="fas fa-save mr-2"></i> Save Results
            </button>
        </div>
        <div class="h-16"></div> <!-- Spacer for fixed footer -->
    </form>
</div>
<?php elseif ($selectedClass): ?>
    <div class="bg-white rounded-lg shadow-lg p-8 text-center text-gray-500">
        <i class="fas fa-user-graduate text-4xl mb-4 text-gray-300"></i>
        <p class="text-lg">No students found in <?= $selectedClass ?>.</p>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.mark-input');
    
    function calculateResults(row) {
        let total = 0;
        let count = 0;
        const rowInputs = row.querySelectorAll('.mark-input');
        
        rowInputs.forEach(input => {
            let val = parseFloat(input.value);
            if (isNaN(val)) val = 0;
            
            // Validate max 100
            if (val > 100) {
                val = 100;
                input.value = 100;
            } else if (val < 0) {
                val = 0;
                input.value = 0;
            }
            
            total += val;
            count++;
        });
        
        // Update Total
        const totalCell = row.querySelector('.total-marks');
        if (totalCell) {
            totalCell.textContent = total;
        }

        // Calculate Grade
        // Assuming 100 marks per subject
        const totalMax = count * 100;
        const percentage = totalMax > 0 ? (total / totalMax) * 100 : 0;
        
        let grade = 'F';
        let colorClass = 'bg-red-100 text-red-800';

        if (percentage >= 80) { grade = 'A+'; colorClass = 'bg-green-100 text-green-800'; }
        else if (percentage >= 70) { grade = 'A'; colorClass = 'bg-green-100 text-green-800'; }
        else if (percentage >= 60) { grade = 'B'; colorClass = 'bg-green-100 text-green-800'; }
        else if (percentage >= 50) { grade = 'C'; colorClass = 'bg-green-100 text-green-800'; }
        else if (percentage >= 40) { grade = 'D'; colorClass = 'bg-green-100 text-green-800'; }
        
        // Update Grade Cell
        const gradeCell = row.querySelector('.grade-cell');
        if (gradeCell) {
            gradeCell.innerHTML = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorClass}">${grade}</span>`;
        }
    }

    inputs.forEach(input => {
        // Enforce max 100 attribute
        input.setAttribute('max', '100');
        input.setAttribute('min', '0');

        input.addEventListener('input', function() {
            const row = this.closest('tr');
            calculateResults(row);
        });
        
        // Initial Calculation check for validation
        input.addEventListener('blur', function() {
             const row = this.closest('tr');
            calculateResults(row);
        });
    });

    const classSelect = document.getElementById('classSelect');
    const examSelect = document.getElementById('examSelect');
    const yearSelect = document.getElementById('yearSelect');
    
    function updateModalInputs() {
        const c = classSelect.value;
        const e = examSelect.value;
        const y = yearSelect.value;
        
        if (document.getElementById('modalClass')) document.getElementById('modalClass').value = c;
        if (document.getElementById('modalExam')) document.getElementById('modalExam').value = e;
        if (document.getElementById('modalYear')) document.getElementById('modalYear').value = y;
    }

    classSelect.addEventListener('change', updateModalInputs);
    examSelect.addEventListener('change', updateModalInputs);
    yearSelect.addEventListener('input', updateModalInputs); 
    
    updateModalInputs(); // Init

    // Auto-submit logic for filters
    function checkAndSubmit() {
        if (classSelect.value && examSelect.value && yearSelect.value) {
            document.getElementById('filterForm').submit();
        }
    }

    classSelect.addEventListener('change', checkAndSubmit);
    examSelect.addEventListener('change', checkAndSubmit);
    yearSelect.addEventListener('change', checkAndSubmit);
});
</script>

<?php include '../includes/footer.php'; ?>
