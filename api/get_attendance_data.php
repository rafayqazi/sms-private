<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$class = isset($_GET['class']) ? $_GET['class'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (empty($class)) {
    echo json_encode([]);
    exit;
}

$db = new Database();

// Get students for the class
$students = $db->filterStudents(['class' => $class, 'sort_by' => 'gr_no', 'order' => 'ASC']);

// Get existing attendance
$existingAttendance = $db->getAttendance($date, $class);

// Merge data
$response = [];
foreach ($students as $student) {
    $status = isset($existingAttendance[$student['id']]) ? $existingAttendance[$student['id']] : '';
    
    $response[] = [
        'id' => $student['id'],
        'gr_no' => $student['gr_no'],
        'student_name' => $student['student_name'],
        'father_name' => $student['father_name'],
        'status' => $status
    ];
}

echo json_encode($response);
?>
