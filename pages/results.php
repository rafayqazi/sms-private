<?php
require_once '../includes/auth_session.php';
require_once '../includes/functions.php';
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

if ($selectedClass && isEditor()) {
    $assigned = getAssignedClasses();
    if (!in_array($selectedClass, $assigned)) {
        die("Unauthorized access to this class.");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_results'])) {
        $results = $_POST['results'];
        $class = $_POST['class'];
        $examType = $_POST['exam_type'];
        $year = $_POST['year'];

        if (isset($_POST['max_marks']) && is_array($_POST['max_marks'])) {
            $db->saveSubjectMaxMarks($class, $examType, $year, $_POST['max_marks']);
        }
        
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
                $activeKeys = $db->getActiveSubjectKeys($class, $examType, $year);
                $extra = isset($marks['other_subjects']) && is_array($marks['other_subjects']) ? $marks['other_subjects'] : [];
                $filteredExtra = [];
                foreach ($extra as $subjectKey => $value) {
                    if (in_array($subjectKey, $activeKeys, true) && !$db->isStandardSubjectKey($subjectKey)) {
                        $filteredExtra[$subjectKey] = $value;
                    }
                }
                $jsonExtra = json_encode($filteredExtra);
                
                $resultData = [
                    'student_id' => $studentId,
                    'class' => $class,
                    'exam_type' => $examType,
                    'year' => $year,
                    'english' => in_array('english', $activeKeys, true) ? ($marks['english'] ?? 0) : 0,
                    'math' => in_array('math', $activeKeys, true) ? ($marks['math'] ?? 0) : 0,
                    'social_studies' => in_array('social_studies', $activeKeys, true) ? ($marks['social_studies'] ?? 0) : 0,
                    'general_science' => in_array('general_science', $activeKeys, true) ? ($marks['general_science'] ?? 0) : 0,
                    'mt' => in_array('mt', $activeKeys, true) ? ($marks['mt'] ?? 0) : 0,
                    'islamiyat' => in_array('islamiyat', $activeKeys, true) ? ($marks['islamiyat'] ?? 0) : 0,
                    'nmt' => in_array('nmt', $activeKeys, true) ? ($marks['nmt'] ?? 0) : 0,
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
            $message = "Subject removed successfully.";
        } else {
            $error = "Could not remove subject. At least one subject must remain.";
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
$activeSubjects = [];
$subjectMaxMarks = [];

if ($selectedClass && $selectedExam && $selectedYear) {
    $students = $db->getStudentsByClass($selectedClass);
    $existingResults = $db->getResults($selectedClass, $selectedExam, $selectedYear);
    $activeSubjects = $db->getActiveSubjects($selectedClass, $selectedExam, $selectedYear);
    $subjectMaxMarks = $db->getSubjectMaxMarks($selectedClass, $selectedExam, $selectedYear);
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-4 md:p-6 rounded-lg shadow-lg mb-6">
    <h1 class="text-2xl md:text-3xl font-bold">Manage Results</h1>
    <p class="text-sm md:text-base text-green-100 mt-1">Enter and view student results</p>
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

<div class="bg-white rounded-lg shadow-lg p-4 md:p-6 mb-6">
    <form method="GET" class="flex flex-col md:flex-row flex-wrap gap-3 md:gap-4 items-stretch md:items-end" id="filterForm">
        <div class="flex flex-col gap-1 w-full md:w-auto md:min-w-[200px]">
            <label class="text-sm font-medium text-gray-700">Class</label>
            <select name="class" id="classSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="">Select Class</option>
                <?php foreach (getAssignedClasses() as $c): ?>
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
        
        <div class="flex flex-wrap gap-2 w-full md:w-auto md:ml-auto items-center justify-center md:justify-end mt-4 md:mt-0">
            <!-- Reset Button for Inputs -->
            <button type="button" onclick="zeroAllMarks()" class="<?= ($selectedClass && !empty($students)) ? '' : 'hidden' ?> bg-orange-100 text-orange-700 border border-orange-200 px-4 py-2 rounded-md hover:bg-orange-200 transition duration-200 flex items-center whitespace-nowrap text-sm">
                <i class="fas fa-undo mr-2 text-xs"></i> Reset results
            </button>

            <!-- Reset Button Triggers Modal (Database) -->
            <button type="button" onclick="document.getElementById('resetConfirmModal').classList.remove('hidden')" class="<?= ($selectedClass && $selectedExam && $selectedYear && !empty($existingResults)) ? '' : 'hidden' ?> bg-red-100 text-red-700 border border-red-200 px-4 py-2 rounded-md hover:bg-red-200 transition duration-200 flex items-center whitespace-nowrap text-sm">
                <i class="fas fa-trash-alt mr-2 text-xs"></i> Clear Database
            </button>

             <button type="button" onclick="document.getElementById('addSubjectModal').classList.remove('hidden')" class="<?= ($selectedClass && $selectedExam && $selectedYear) ? '' : 'hidden' ?> bg-indigo-100 text-indigo-700 border border-indigo-200 px-4 py-2 rounded-md hover:bg-indigo-200 transition duration-200 flex items-center whitespace-nowrap text-sm">
                <i class="fas fa-book mr-2 text-xs"></i> Manage Subjects
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

<!-- Manage Subjects Modal -->
<div id="addSubjectModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('addSubjectModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-1" id="modal-title">Manage Subjects</h3>
                    <p class="text-sm text-gray-500 mb-4">Add or remove subjects for this class. Each class can have different subjects.</p>
                    <input type="hidden" name="class" id="modalClass" value="<?= $selectedClass ?>">
                    <input type="hidden" name="exam_type" id="modalExam" value="<?= $selectedExam ?>">
                    <input type="hidden" name="year" id="modalYear" value="<?= $selectedYear ?>">
                    
                    <div class="mb-4">
                        <label for="new_subject_name" class="block text-sm font-medium text-gray-700 mb-1">Add New Subject</label>
                        <input type="text" name="new_subject_name" id="new_subject_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., Computer, Drawing, Haqu">
                    </div>
                    
                    <?php if (!empty($activeSubjects)): ?>
                    <div class="mt-6 border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Current Subjects</h4>
                        <div class="space-y-2 max-h-56 overflow-y-auto">
                            <?php foreach ($activeSubjects as $subject): ?>
                            <div class="flex justify-between items-center bg-gray-50 p-2 rounded border border-gray-200">
                                <span class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($subject['label']) ?></span>
                                <button type="submit" name="delete_subject" value="<?= htmlspecialchars($subject['key']) ?>" onclick="return confirm('Remove <?= htmlspecialchars($subject['label']) ?> from this class?');" class="text-red-600 hover:text-red-800 text-xs font-semibold px-2 py-1 rounded hover:bg-red-50 transition-colors" <?= count($activeSubjects) <= 1 ? 'disabled title="At least one subject required"' : '' ?>>
                                    <i class="fas fa-trash-alt mr-1"></i> Remove
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
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($selectedClass && $selectedExam && $selectedYear): ?>
<form id="deleteSubjectForm" method="POST" class="hidden">
    <input type="hidden" name="class" value="<?= htmlspecialchars($selectedClass) ?>">
    <input type="hidden" name="exam_type" value="<?= htmlspecialchars($selectedExam) ?>">
    <input type="hidden" name="year" value="<?= htmlspecialchars($selectedYear) ?>">
    <input type="hidden" name="delete_subject" id="deleteSubjectInput" value="">
</form>
<?php endif; ?>

<?php if (!empty($students)): ?>
<div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
    <!-- Save Results Form -->
    <form method="POST">
        <input type="hidden" name="class" id="hiddenClass" value="<?= $selectedClass ?>">
        <input type="hidden" name="exam_type" id="hiddenExam" value="<?= $selectedExam ?>">
        <input type="hidden" name="year" id="hiddenYear" value="<?= $selectedYear ?>">
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-20 w-12 min-w-[3rem] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">S#</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-[3rem] bg-gray-50 z-20 w-20 min-w-[5rem] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">GR NO</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-[8rem] bg-gray-50 z-20 w-48 min-w-[12rem] shadow-[4px_0_10px_-4px_rgba(0,0,0,0.1)]">NAME</th>
                        <?php foreach ($activeSubjects as $subject): ?>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24 min-w-[6rem]">
                            <div class="flex items-center justify-between gap-1">
                                <span><?= htmlspecialchars($subject['label']) ?></span>
                                <?php if (count($activeSubjects) > 1): ?>
                                <button type="button" onclick="deleteSubject('<?= htmlspecialchars($subject['key'], ENT_QUOTES) ?>', '<?= htmlspecialchars($subject['label'], ENT_QUOTES) ?>')" class="text-red-500 hover:text-red-700 text-sm leading-none" title="Remove subject">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </th>
                        <?php endforeach; ?>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24 min-w-[6rem]">TOTAL</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24 min-w-[6rem]">PERCENTAGE</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16 min-w-[4rem]">GRADE</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20 min-w-[5rem]">ACTION</th>
                    </tr>
                    <tr class="bg-indigo-50 border-t border-indigo-100">
                        <th colspan="3" class="px-3 py-2 text-left text-[10px] font-bold text-indigo-700 uppercase tracking-wider sticky left-0 bg-indigo-50 z-20 shadow-[4px_0_10px_-4px_rgba(0,0,0,0.1)]">
                            Max Marks
                        </th>
                        <?php foreach ($activeSubjects as $subject): ?>
                        <th class="px-2 py-2">
                            <input type="number" name="max_marks[<?= htmlspecialchars($subject['key']) ?>]" value="<?= (int)($subjectMaxMarks[$subject['key']] ?? 100) ?>" min="1" max="500" class="max-marks-input w-16 px-1 py-1 border border-indigo-200 rounded text-xs text-center font-semibold text-indigo-800 bg-white focus:ring-indigo-500 focus:border-indigo-500" data-subject="<?= htmlspecialchars($subject['key']) ?>">
                        </th>
                        <?php endforeach; ?>
                        <th colspan="4" class="px-2 py-2 text-[10px] text-indigo-600 italic font-medium">
                            Set per subject, then save results
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $i = 1; foreach ($students as $student): 
                        $result = isset($existingResults[$student['id']]) ? $existingResults[$student['id']] : null;
                        $otherSubjects = ($result && isset($result['other_subjects'])) ? json_decode($result['other_subjects'], true) : [];
                    ?>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 font-medium sticky left-0 bg-white z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-12 min-w-[3rem]"><?= $i++ ?></td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-[3rem] bg-white z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-20 min-w-[5rem]"><?= $student['gr_no'] ?></td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 sticky left-[8rem] bg-white z-20 shadow-[4px_0_10px_-4px_rgba(0,0,0,0.1)] w-48 min-w-[12rem]"><?= $student['student_name'] ?></td>

                        <?php foreach ($activeSubjects as $subject):
                            if ($subject['type'] === 'standard') {
                                $markValue = $result ? $result[$subject['key']] : '0';
                                $inputName = 'results[' . $student['id'] . '][' . $subject['key'] . ']';
                            } else {
                                $markValue = isset($otherSubjects[$subject['key']]) ? $otherSubjects[$subject['key']] : '0';
                                $inputName = 'results[' . $student['id'] . '][other_subjects][' . $subject['key'] . ']';
                            }
                            $isAbsent = strtoupper((string)$markValue) === 'A';
                        ?>
                        <td class="px-2 py-2">
                            <div class="flex items-center gap-1">
                                <input type="text" name="<?= $inputName ?>" value="<?= htmlspecialchars($markValue) ?>" data-subject="<?= htmlspecialchars($subject['key']) ?>" class="mark-input w-16 px-1 py-1 border rounded text-xs focus:ring-blue-500 focus:border-blue-500 text-center" placeholder="0" <?= $isAbsent ? 'readonly' : '' ?>>
                                <label class="flex items-center text-[10px] text-gray-500 cursor-pointer hover:text-blue-600" title="Absent">
                                    <input type="checkbox" class="absent-check scale-75" <?= $isAbsent ? 'checked' : '' ?>> A
                                </label>
                            </div>
                        </td>
                        <?php endforeach; ?>
                        
                        <td class="px-2 py-2 font-bold text-center total-marks text-gray-700 bg-gray-50 border rounded-md min-w-[3rem] p-1">
                            <?= $result ? $result['total_obtained'] : '0' ?>
                        </td>
                        <td class="px-2 py-2 font-bold text-center percentage-cell text-gray-700 bg-gray-50 border rounded-md min-w-[3rem] p-1">
                            <?= $result ? ($result['percentage'] . '%') : '0%' ?>
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
    const maxMarksInputs = document.querySelectorAll('.max-marks-input');

    function getMaxMarksMap() {
        const map = {};
        document.querySelectorAll('.max-marks-input').forEach(input => {
            const subject = input.dataset.subject;
            const max = parseInt(input.value, 10);
            map[subject] = (!isNaN(max) && max > 0) ? max : 100;
        });
        return map;
    }

    function getSubjectMax(subject) {
        const map = getMaxMarksMap();
        return map[subject] || 100;
    }
    
    function calculateResults(row) {
        let total = 0;
        let totalMax = 0;
        let failedSubject = false;
        const rowInputs = row.querySelectorAll('.mark-input');
        
        rowInputs.forEach(input => {
            const subject = input.dataset.subject;
            const subjectMax = getSubjectMax(subject);
            const passMark = subjectMax * 0.33;
            let rawVal = input.value.trim().toUpperCase();
            let val = 0;

            totalMax += subjectMax;

            if (rawVal === 'A') {
                failedSubject = true;
                val = 0;
            } else {
                val = parseFloat(rawVal);
                if (isNaN(val)) val = 0;
                
                if (val > subjectMax) {
                    val = subjectMax;
                    input.value = subjectMax;
                } else if (val < 0) {
                    val = 0;
                    input.value = 0;
                }

                if (val < passMark) {
                    failedSubject = true;
                }
            }
            
            total += val;
        });
        
        // Update Total
        const totalCell = row.querySelector('.total-marks');
        if (totalCell) {
            totalCell.textContent = total;
        }

        const percentage = totalMax > 0 ? (total / totalMax) * 100 : 0;
        
        // Update Percentage Cell
        const percentageCell = row.querySelector('.percentage-cell');
        if (percentageCell) {
            percentageCell.textContent = percentage.toFixed(2) + '%';
        }
        
        let grade = 'F';
        let colorClass = 'bg-red-100 text-red-800';

        if (!failedSubject) {
            if (percentage >= 80) { grade = 'A+'; colorClass = 'bg-green-100 text-green-800'; }
            else if (percentage >= 70) { grade = 'A'; colorClass = 'bg-green-100 text-green-800'; }
            else if (percentage >= 60) { grade = 'B'; colorClass = 'bg-green-100 text-green-800'; }
            else if (percentage >= 50) { grade = 'C'; colorClass = 'bg-green-100 text-green-800'; }
            else if (percentage >= 33) { grade = 'D'; colorClass = 'bg-green-100 text-green-800'; }
        }
        
        // Update Grade Cell
        const gradeCell = row.querySelector('.grade-cell');
        if (gradeCell) {
            gradeCell.innerHTML = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorClass}">${grade}</span>`;
        }
    }

    maxMarksInputs.forEach(input => {
        input.addEventListener('input', function() {
            const max = parseInt(this.value, 10);
            if (!isNaN(max) && max < 1) this.value = 1;
            if (!isNaN(max) && max > 500) this.value = 500;
            document.querySelectorAll('tbody tr').forEach(row => calculateResults(row));
        });
    });

    inputs.forEach(input => {
        // Enforce basic validation
        input.addEventListener('input', function() {
            // Allow only numbers and 'A'/'a'
            this.value = this.value.replace(/[^0-9Aa.]/g, '');
            const row = this.closest('tr');
            calculateResults(row);
        });
        
        // Initial Calculation check for validation
        input.addEventListener('blur', function() {
             const row = this.closest('tr');
            calculateResults(row);
        });
    });

    // Handle Absent Checkbox Toggle
    document.querySelectorAll('.absent-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const container = this.closest('div');
            const input = container.querySelector('.mark-input');
            const row = this.closest('tr');

            if (this.checked) {
                input.value = 'A';
                input.setAttribute('readonly', true);
            } else {
                input.value = '0';
                input.removeAttribute('readonly');
            }
            calculateResults(row);
        });
    });

    window.zeroAllMarks = function() {
        if (confirm('Are you sure you want to set all marks to 0? This will clear all current entries on this screen.')) {
            document.querySelectorAll('.mark-input').forEach(input => {
                input.value = '0';
                input.removeAttribute('readonly');
            });
            document.querySelectorAll('.absent-check').forEach(check => {
                check.checked = false;
            });
            document.querySelectorAll('tbody tr').forEach(row => {
                calculateResults(row);
            });
        }
    }

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

    document.querySelectorAll('tbody tr').forEach(row => calculateResults(row));

    window.deleteSubject = function(subjectKey, subjectLabel) {
        if (!confirm('Remove ' + subjectLabel + ' from this class?')) {
            return;
        }
        const form = document.getElementById('deleteSubjectForm');
        const input = document.getElementById('deleteSubjectInput');
        if (!form || !input) {
            return;
        }
        input.value = subjectKey;
        form.submit();
    };
});
</script>

<?php include '../includes/footer.php'; ?>
