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
// Map collections by gr_no
$collectionsMap = [];
foreach ($collections as $c) {
    $collectionsMap[$c['gr_no']] = $c;
}

$feeStructure = $db->getFeeStructure();
$classFees = $feeStructure[$class] ?? ['monthly_fee' => 0];
$assignedMonthly = (float)$classFees['monthly_fee'];

$results = [];
foreach ($allStudents as $s) {
    // Treat 'Active', '0', or empty status as active students
    $status = $s['student_status'] ?? '';
    if ($status === 'Active' || $status === '0' || $status === '') {
        $gr = $s['gr_no'];
        
        $previous_debt = $db->getStudentPreviousDebt($gr, $month);
        
        $pStatus = 'Unpaid';
        $debt = $assignedMonthly + $previous_debt;
        
        if (isset($collectionsMap[$gr])) {
            $p = $collectionsMap[$gr];
            $paid = (float)$p['amount_paid'];
            $discount = (float)($p['discount'] ?? 0);
            $admission = (float)($p['admission_fee'] ?? 0);
            $exam = (float)($p['exam_fee'] ?? 0);
            $other = (float)($p['other_fee'] ?? 0);
            
            $due_tuition = (isset($p['tuition_fee']) && $p['tuition_fee'] !== '') ? (float)$p['tuition_fee'] : $assignedMonthly;
            
            $expected = $due_tuition + $admission + $exam + $other - $discount + $previous_debt;
            if ($paid >= $expected) {
                $pStatus = 'Paid';
                $debt = 0;
            } else {
                $pStatus = $paid > 0 ? 'Partial' : 'Unpaid';
                $debt = $expected - $paid;
            }
        } else {
            if ($debt <= 0) {
                $pStatus = 'Paid';
            }
        }
        
        $results[] = [
            'gr_no' => $gr,
            'student_name' => $s['student_name'],
            'status' => $pStatus,
            'debt' => $debt
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
