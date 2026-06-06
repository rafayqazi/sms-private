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
$feeClass = $db->getStudentFeeClass($student);
$classFees = $feeStructure[$feeClass] ?? [
    'monthly_fee' => 0,
    'admission_fee' => 0,
    'exam_fee' => 0
];

$month = $_GET['month'] ?? '';
$history = $db->getStudentFeeHistory($gr_no);

$existing_payment = null;
if ($month) {
    foreach ($history as $h) {
        if ($h['month_for'] == $month) {
            $existing_payment = $h;
            break;
        }
    }
}

$previous_debt = 0;
$previous_debt_breakdown = [];
if ($month) {
    $previous_debt = $db->getStudentPreviousDebt($gr_no, $month);
    $previous_debt_breakdown = $db->getStudentPreviousDebtBreakdown($gr_no, $month);
}

echo json_encode([
    'student' => $student,
    'structure' => $classFees,
    'existing_payment' => $existing_payment,
    'previous_debt' => $previous_debt,
    'previous_debt_breakdown' => $previous_debt_breakdown
]);
