<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$query = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';

// Get assigned classes for the logged-in teacher
$assignedClasses = getAssignedClasses();

if (empty($assignedClasses) || (count($assignedClasses) === 1 && empty($assignedClasses[0]))) {
    echo json_encode([]);
    exit;
}

$allStudents = $db->readData();
$results = [];

foreach ($allStudents as $student) {
    if (isset($student['current_class']) && in_array($student['current_class'], $assignedClasses)) {
        if (!isset($student['student_status']) || $student['student_status'] !== 'Alumni') {
            $studentName = isset($student['student_name']) ? strtolower($student['student_name']) : '';
            $grNo = isset($student['gr_no']) ? strtolower(trim($student['gr_no'])) : '';

            // If query is empty, or explicitly matches
            if ($query === '' || strpos($studentName, $query) !== false || strpos($grNo, $query) !== false) {
                $results[] = [
                    'id' => $student['id'],
                    'name' => $student['student_name'] ?? 'Unknown',
                    'gr_no' => $student['gr_no'] ?? 'N/A',
                    'class' => $student['current_class']
                ];
            }
        }
    }
}

// Limit the results to keep the dropdown clean
$results = array_slice($results, 0, 15);

echo json_encode($results);
