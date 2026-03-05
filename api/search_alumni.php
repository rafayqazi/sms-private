<?php
header('Content-Type: application/json');
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    file_put_contents('../debug_search.log', date('Y-m-d H:i:s') . " - Unauthorized access attempt\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
file_put_contents('../debug_search.log', date('Y-m-d H:i:s') . " - Search Query: '$query'\n", FILE_APPEND);

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$allStudents = $db->readData();
file_put_contents('../debug_search.log', date('Y-m-d H:i:s') . " - Total Students Loaded: " . count($allStudents) . "\n", FILE_APPEND);

$results = [];
$count = 0;
$limit = 50;

foreach ($allStudents as $student) {
    // Must be Alumni
    if (($student['student_status'] ?? '') !== 'Alumni') {
        continue;
    }

    // Check match
    $nameMatch = stripos($student['student_name'] ?? '', $query) !== false;
    $grMatch = stripos($student['gr_no'] ?? '', $query) !== false;

    if ($nameMatch || $grMatch) {
        $results[] = [
            'id' => $student['id'],
            'value' => $student['student_name'], // For display
            'gr_no' => $student['gr_no'],
            'father_name' => $student['father_name'] ?? 'N/A',
            'label' => $student['student_name'] . ' (GR: ' . $student['gr_no'] . ')',
            'graduation_year' => $student['graduation_year'] ?? 'N/A',
            'last_class' => $student['last_class'] ?? 'N/A'
        ];
        $count++;
    }

    if ($count >= $limit) {
        break;
    }
}

echo json_encode($results);
