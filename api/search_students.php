<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? $_GET['query'] : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$students = $db->filterStudents(['search' => $query]);

// Limit results
$students = array_slice($students, 0, 10);

$results = array_map(function($s) {
    return [
        'id' => $s['id'],
        'student_name' => $s['student_name'],
        'gr_no' => $s['gr_no'],
        'current_class' => $s['current_class']
    ];
}, $students);

echo json_encode($results);
?>
