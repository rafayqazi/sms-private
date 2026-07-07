<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$gr_no = trim($_POST['gr_no'] ?? '');
$monthly_fee = $_POST['monthly_fee'] ?? null;
$use_standard = isset($_POST['use_standard']) && $_POST['use_standard'] === '1';

if (!$gr_no) {
    echo json_encode(['error' => 'Student GR No is required']);
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
$classFees = $feeStructure[$feeClass] ?? ['monthly_fee' => 0];
$standardMonthly = (float)$classFees['monthly_fee'];

if ($use_standard) {
    if (!$db->removeStudentCustomFee($gr_no)) {
        echo json_encode(['error' => 'Failed to reset student fee']);
        exit;
    }
    echo json_encode([
        'success' => true,
        'message' => 'Student fee reset to class standard',
        'assigned_monthly_fee' => $standardMonthly,
        'standard_monthly_fee' => $standardMonthly,
        'has_custom_fee' => false
    ]);
    exit;
}

if ($monthly_fee === null || $monthly_fee === '') {
    echo json_encode(['error' => 'Monthly fee amount is required']);
    exit;
}

$monthly_fee = (float)$monthly_fee;
if ($monthly_fee <= 0) {
    echo json_encode(['error' => 'Monthly fee must be greater than 0']);
    exit;
}

if (!$db->setStudentCustomFee($gr_no, $monthly_fee)) {
    echo json_encode(['error' => 'Failed to save student fee']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Student fee updated successfully',
    'assigned_monthly_fee' => $monthly_fee,
    'standard_monthly_fee' => $standardMonthly,
    'has_custom_fee' => abs($monthly_fee - $standardMonthly) > 0.01
]);
