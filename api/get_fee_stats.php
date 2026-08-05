<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$month = $_GET['month'] ?? date('Y-m');

$db = new Database();

// Fee stats
$feeStats = $db->getFeeStats($month);

// Defaulters count + total debt for this month
$defaulters = $db->getDefaulters($month);
$defaulterTotalDebt = 0;
foreach ($defaulters as $d) {
    $defaulterTotalDebt += (float)($d['debt'] ?? 0);
}

// Build recent collections with student names
$allStudents = $db->readData();
$sMap = [];
foreach ($allStudents as $st) {
    $info = [
        'student_name' => $st['student_name'],
        'current_class' => $st['current_class'] ?? ''
    ];
    if (!empty($st['gr_no'])) $sMap[$st['gr_no']] = $info;
    if (!empty($st['id'])) $sMap[$st['id']] = $info;
}

$recent = [];
foreach (array_slice($feeStats['recent'], 0, 5) as $r) {
    $studentInfo = $sMap[$r['gr_no']] ?? ['student_name' => 'Unknown', 'current_class' => ''];
    $recent[] = [
        'gr_no' => $r['gr_no'],
        'student_name' => $studentInfo['student_name'],
        'class' => $studentInfo['current_class'],
        'month_for' => $r['month_for'],
        'amount_paid' => (float)($r['amount_paid'] ?? 0),
        'payment_date' => $r['payment_date'],
        'payment_method' => $r['payment_method'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'this_month' => $feeStats['this_month'],
    'today' => $feeStats['today'],
    'class_breakdown' => $feeStats['class_breakdown'],
    'recent' => $recent,
    'defaulter_count' => count($defaulters),
    'defaulter_total_debt' => $defaulterTotalDebt,
    'month_label' => date('F Y', strtotime($month))
]);
