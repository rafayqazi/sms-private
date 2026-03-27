<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$db = new Database();

$teacher_id = $_POST['teacher_id'] ?? '';
$month = $_POST['month'] ?? date('Y-m');
$base_salary = floatval($_POST['base_salary'] ?? 0);
$deduction = floatval($_POST['deduction'] ?? 0);
$notes = $_POST['notes'] ?? '';
$payment_date = $_POST['payment_date'] ?? date('Y-m-d');

if (empty($teacher_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Teacher ID is required']);
    exit;
}

$net_salary = $base_salary - $deduction;

$data = [
    'teacher_id' => $teacher_id,
    'month' => $month,
    'base_salary' => $base_salary,
    'deduction' => $deduction,
    'net_salary' => $net_salary,
    'payment_date' => $payment_date,
    'notes' => $notes
];

if ($db->payTeacherSalary($data)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Salary paid successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
}
