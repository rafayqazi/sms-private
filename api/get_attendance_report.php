<?php
require_once '../includes/auth_session.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$class = isset($_GET['class']) ? $_GET['class'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (empty($class)) {
    echo json_encode(['stats' => [], 'students' => []]);
    exit;
}

if (isEditor()) {
    $assigned = getAssignedClasses();
    if (!in_array($class, $assigned)) {
        echo json_encode(['stats' => [], 'students' => []]);
        exit;
    }
}

$db = new Database();

// Get students
$students = $db->filterStudents(['class' => $class, 'sort_by' => 'gr_no', 'order' => 'ASC']);

// Get attendance
$attendanceData = $db->getAttendance($date, $class);

// Calculate Stats & Prepare Response
$stats = ['P' => 0, 'A' => 0, 'L' => 0, 'Unmarked' => 0, 'Total' => count($students)];
$studentList = [];

foreach ($students as $student) {
    $status = isset($attendanceData[$student['id']]) ? $attendanceData[$student['id']] : '';
    
    if ($status == 'P') $stats['P']++;
    elseif ($status == 'A') $stats['A']++;
    elseif ($status == 'L') $stats['L']++;
    else $stats['Unmarked']++;

    $studentList[] = [
        'gr_no' => $student['gr_no'],
        'student_name' => $student['student_name'],
        'father_name' => $student['father_name'],
        'status' => $status
    ];
}

echo json_encode([
    'stats' => $stats,
    'students' => $studentList
]);
?>
