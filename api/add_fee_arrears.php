<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$gr_no = trim($_POST['gr_no'] ?? '');
$month = trim($_POST['month_for'] ?? '');
$amount = $_POST['amount'] ?? 0;
$remarks = trim($_POST['remarks'] ?? '');

if (!$gr_no || !$month || !$amount || $remarks === '') {
    echo json_encode(['error' => 'GR No, month, amount and remarks are required']);
    exit;
}

if ((float)$amount <= 0) {
    echo json_encode(['error' => 'Amount must be greater than 0']);
    exit;
}

$db = new Database();
if (!$db->getStudentByGrNo($gr_no)) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

if ($db->addStudentFeeArrears($gr_no, $month, $amount, $remarks)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to add arrears']);
}
