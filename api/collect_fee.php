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

$data = [
    'gr_no' => $gr_no,
    'payment_date' => date('Y-m-d'),
    'month_for' => $month,
    'amount_paid' => $amount,
    'discount' => $_POST['discount'] ?? 0,
    'payment_method' => $_POST['payment_method'] ?? 'Cash',
    'notes' => $_POST['notes'] ?? ''
];

$transaction_id = $db->recordFeePayment($data);

if ($transaction_id) {
    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id
    ]);
} else {
    echo json_encode(['error' => 'Failed to record payment']);
}
