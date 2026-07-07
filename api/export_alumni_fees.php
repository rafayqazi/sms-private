<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();

// Get filter params
$search = $_GET['search'] ?? '';
$yearFilter = $_GET['year'] ?? '';

// Get all alumni
$allStudents = $db->readData();
$alumniStudents = array_filter($allStudents, function($student) use ($search, $yearFilter) {
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    if (!$isAlumni) return false;
    if ($yearFilter) {
        $gradYear = $student['graduation_year'] ?? (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : '');
        if ($gradYear !== $yearFilter) return false;
    }
    if ($search) {
        $nameMatch = stripos($student['student_name'] ?? '', $search) !== false;
        $grMatch = stripos($student['gr_no'] ?? '', $search) !== false;
        if (!$nameMatch && !$grMatch) return false;
    }
    return true;
});

// Filter only uncleared
$uncleared = [];
foreach ($alumniStudents as $student) {
    $outstanding = $db->getAlumniOutstandingBalance($student['gr_no']);
    if ($outstanding >= 0.01) {
        $uncleared[] = [
            'gr_no' => $student['gr_no'],
            'student_name' => $student['student_name'],
            'father_name' => $student['father_name'],
            'last_class' => $student['last_class'] ?? $student['current_class'] ?? 'N/A',
            'graduation_year' => $student['graduation_year'] ?? (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : ''),
            'outstanding' => $outstanding
        ];
    }
}

$filename = 'Uncleared_Alumni_Fees_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Uncleared Alumni Fees Report']);
fputcsv($output, ['Generated', date('d M Y, h:i A')]);
fputcsv($output, []);

fputcsv($output, ['S.No', 'GR No', 'Student Name', "Father's Name", 'Last Class', 'Graduation Year', 'Outstanding Amount (Rs.)']);

$serial = 1;
$totalOutstanding = 0;
foreach ($uncleared as $u) {
    $totalOutstanding += $u['outstanding'];
    fputcsv($output, [
        $serial++,
        $u['gr_no'],
        $u['student_name'],
        $u['father_name'],
        $u['last_class'],
        $u['graduation_year'],
        number_format($u['outstanding'], 2)
    ]);
}

fputcsv($output, []);
fputcsv($output, ['', '', '', '', '', 'TOTAL', number_format($totalOutstanding, 2)]);
fclose($output);
exit;
