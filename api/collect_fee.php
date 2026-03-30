<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$gr_no = $_POST['gr_no'] ?? '';
$amount = $_POST['amount_paid'] ?? 0;
$month = $_POST['month_for'] ?? '';

if (!$gr_no || !$amount || !$month) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$db = new Database();
$transaction_id = $_POST['transaction_id'] ?? '';

$data = [
    'gr_no' => $gr_no,
    'month_for' => $month,
    'amount_paid' => $amount,
    'discount' => $_POST['discount'] ?? 0,
    'payment_method' => $_POST['payment_method'] ?? 'Cash',
    'notes' => $_POST['notes'] ?? '',
    'admission_fee' => $_POST['admission_fee'] ?? 0,
    'exam_fee' => $_POST['exam_fee'] ?? 0,
    'other_fee' => $_POST['other_fee'] ?? 0,
    'other_label' => $_POST['other_label'] ?? ''
];

if ($transaction_id) {
    // Updating existing record
    $result = $db->updateFeePayment($transaction_id, $data);
} else {
    // New record
    $data['payment_date'] = date('Y-m-d');
    $transaction_id = $db->recordFeePayment($data);
    $result = (bool)$transaction_id;
}

if ($result) {
    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id
    ]);
} else {
    echo json_encode(['error' => 'Failed to record/update payment']);
}
