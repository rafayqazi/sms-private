<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$class = $_GET['class'] ?? '';
$month = $_GET['month'] ?? date('Y-m');

if (!$class) {
    echo json_encode(['error' => 'Class not specified']);
    exit;
}

$db = new Database();
$allStudents = $db->filterStudents(['class' => $class]);
$collections = $db->getFeeCollections(['month' => $month]);
$paidGrNos = array_column($collections, 'gr_no');

$results = [];
foreach ($allStudents as $s) {
    // Treat 'Active', '0', or empty status as active students
    $status = $s['student_status'] ?? '';
    if ($status === 'Active' || $status === '0' || $status === '') {
        $results[] = [
            'gr_no' => $s['gr_no'],
            'student_name' => $s['student_name'],
            'status' => in_array($s['gr_no'], $paidGrNos) ? 'Paid' : 'Unpaid'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'data' => $results,
    'debug' => [
        'class_requested' => $class,
        'filtered_count' => count($allStudents),
        'results_count' => count($results)
    ]
]);
exit;
