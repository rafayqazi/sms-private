<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/fee_history_report.php';

$db = new Database();
$params = [
    'month' => $_GET['month'] ?? '',
    'class' => $_GET['class'] ?? '',
    'stage' => $_GET['stage'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$rows = buildFeeHistoryReport($db, $params);
$title = getFeeHistoryReportTitle($params);
$filename = 'Fee_Collection_Report_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $title) . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Fee Collection Report — ' . $title]);
fputcsv($output, ['Generated', date('d M Y, h:i A')]);
fputcsv($output, []);

fputcsv($output, [
    'S.No', 'Stage', 'Class', 'GR No', 'Student Name', "Father's Name",
    'For Month', 'Status', 'Tuition Fee', 'Admission Fee', 'Exam Fee', 'Other Fee',
    'Discount', 'Month Fee', 'Arrears', 'Total Due', 'Amount Paid', 'Remaining Debt',
    'Payment Method', 'Payment Date', 'Remarks'
]);

$serial = 1;
$totPaid = 0;
$totDebt = 0;
foreach ($rows as $r) {
    $totPaid += $r['amount_paid'];
    $totDebt += $r['remaining_debt'];
    fputcsv($output, [
        $serial++,
        $r['stage'],
        $r['class'],
        $r['gr_no'],
        $r['student_name'],
        $r['father_name'],
        $r['month_label'],
        $r['status'],
        $r['tuition_fee'],
        $r['admission_fee'],
        $r['exam_fee'],
        $r['other_fee'],
        $r['discount'],
        $r['month_fee'],
        $r['arrears'],
        $r['total_due'],
        $r['amount_paid'],
        $r['remaining_debt'],
        $r['payment_method'],
        $r['payment_date'],
        $r['remarks']
    ]);
}

fputcsv($output, []);
fputcsv($output, ['', '', '', '', '', '', '', 'TOTALS', '', '', '', '', '', '', '', '', $totPaid, $totDebt, '', '', '']);
fclose($output);
exit;
