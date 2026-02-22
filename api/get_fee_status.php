<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$gr_no = $_GET['gr_no'] ?? '';
if (!$gr_no) {
    echo json_encode(['error' => 'GR No mapping failed']);
    exit;
}

$db = new Database();
$student = $db->getStudentByGrNo($gr_no);

if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

$feeStructure = $db->getFeeStructure();
$classFees = $feeStructure[$student['current_class']] ?? [
    'monthly_fee' => 0,
    'admission_fee' => 0,
    'exam_fee' => 0
];

$history = $db->getStudentFeeHistory($gr_no);

echo json_encode([
    'student' => $student,
    'structure' => $classFees,
    'history' => $history
]);
