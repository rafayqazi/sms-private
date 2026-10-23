<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$class = isset($_GET['class']) ? trim($_GET['class']) : '';
$examName = isset($_GET['exam_name']) ? trim($_GET['exam_name']) : '';
$subjects = isset($_GET['subjects']) && is_array($_GET['subjects']) ? $_GET['subjects'] : [];
$maxMarks = isset($_GET['max_marks']) && is_array($_GET['max_marks']) ? $_GET['max_marks'] : [];
$startingSeatNo = isset($_GET['starting_seat_no']) && is_numeric($_GET['starting_seat_no']) ? intval($_GET['starting_seat_no']) : null;

if (!$class) {
    http_response_code(400);
    exit('Class is required to generate the Marking Award List CSV.');
}

$allowedClasses = getAssignedClasses();
if (!empty($allowedClasses) && !in_array($class, $allowedClasses) && !isAdmin() && !isSuperAdmin()) {
    http_response_code(403);
    exit('Unauthorized access for this class.');
}

// Clean up subjects and map them with max marks
$subjectList = [];
for ($i = 0; $i < count($subjects); $i++) {
    $subjName = trim($subjects[$i] ?? '');
    if ($subjName !== '') {
        $marksVal = isset($maxMarks[$i]) && is_numeric($maxMarks[$i]) ? intval($maxMarks[$i]) : 100;
        $subjectList[] = [
            'name' => strtoupper($subjName),
            'max_marks' => $marksVal
        ];
    }
}

if (empty($subjectList)) {
    $subjectList[] = ['name' => 'ALL SUBJECTS', 'max_marks' => 100];
}

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();

// Filter students for the selected class (excluding Alumni)
$studentsFiltered = array_filter($allStudents, function ($student) use ($class) {
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    return !$isAlumni && isset($student['current_class']) && ($class === 'All' || $student['current_class'] === $class);
});

// Natural comparison helper for GR No, with student name fallback (matching print_slips.php)
$studentSort = function ($a, $b) {
    $grA = trim((string)($a['gr_no'] ?? ''));
    $grB = trim((string)($b['gr_no'] ?? ''));
    if ($grA !== '' && $grB !== '' && is_numeric($grA) && is_numeric($grB)) {
        $diff = intval($grA) - intval($grB);
        if ($diff !== 0) {
            return $diff;
        }
    } else {
        $cmp = strnatcasecmp($grA, $grB);
        if ($cmp !== 0) {
            return $cmp;
        }
    }
    return strcasecmp(trim($a['student_name'] ?? ''), trim($b['student_name'] ?? ''));
};

$boys = [];
$girls = [];
$others = [];

foreach ($studentsFiltered as $st) {
    $gender = strtolower(trim($st['gender'] ?? ''));
    if (in_array($gender, ['male', 'm', 'boy', 'boys'])) {
        $boys[] = $st;
    } elseif (in_array($gender, ['female', 'f', 'girl', 'girls'])) {
        $girls[] = $st;
    } else {
        $others[] = $st;
    }
}

// Sort boys by GR No, then girls by GR No (matching print_slips.php)
usort($boys, $studentSort);
usort($girls, $studentSort);
usort($others, $studentSort);

$orderedStudents = array_merge($boys, $girls, $others);

// Allot seat numbers sequentially: {CLASS}-{1}, {CLASS}-{2}, etc. (matching print_slips.php)
$classPrefix = strtoupper(trim($class));
$seatCounter = ($startingSeatNo !== null && $startingSeatNo > 0) ? $startingSeatNo : 1;
$students = [];
foreach ($orderedStudents as $st) {
    $st['seat_no'] = $classPrefix . '-' . $seatCounter;
    $students[] = $st;
    $seatCounter++;
}

// Build sanitized filename
$cleanClass = preg_replace('/[^a-zA-Z0-9_-]/', '_', $class);
$filename = "Marking_Award_List_Class_{$cleanClass}_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM so Excel displays all Unicode/special characters correctly
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// School and Exam Metadata
$schoolName = $settings['school_name'] ?? 'SCHOOL NAME';
fputcsv($output, [$schoolName]);
if (!empty($examName)) {
    fputcsv($output, [$examName]);
}
fputcsv($output, ['MARKING AWARD LIST']);
fputcsv($output, [
    "Class: $class",
    "Total Subjects: " . count($subjectList),
    "Total Students: " . count($students)
]);
fputcsv($output, []); // Empty separator row

// Build dynamic table headers
$headers = ['S.No', 'Seat #', 'GR No', 'Student Name'];
$grandTotalMaxMarks = 0;
foreach ($subjectList as $subj) {
    $grandTotalMaxMarks += intval($subj['max_marks']);
    $headers[] = $subj['name'] . ' (' . $subj['max_marks'] . ')';
}
$headers[] = 'TOTAL (' . $grandTotalMaxMarks . ')';

fputcsv($output, $headers);

$counter = 1;
foreach ($students as $student) {
    $seatNoDisplay = $student['seat_no'] ?? ($counter);

    $row = [
        $counter,
        $seatNoDisplay,
        $student['gr_no'] ?? '',
        $student['student_name'] ?? ''
    ];

    // Blank cells for each subject mark entry
    foreach ($subjectList as $subj) {
        $row[] = '';
    }

    // Blank cell for Total mark entry
    $row[] = '';

    fputcsv($output, $row);
    $counter++;
}

fputcsv($output, []);
fputcsv($output, ['Total Students:', count($students)]);

fclose($output);
exit;
