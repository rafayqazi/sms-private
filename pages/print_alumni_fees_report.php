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

$settings = $db->getSchoolSettings();
$schoolName = $settings['school_name'] ?? 'School Name';
$totalOutstanding = array_sum(array_column($uncleared, 'outstanding'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uncleared Alumni Fees Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; padding: 40px; background: #fff; color: #333; }
        @media print {
            body { padding: 20px; }
            .no-print { display: none !important; }
        }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .header h1 { font-size: 22px; font-weight: 700; color: #111; margin-bottom: 5px; }
        .header p { color: #6b7280; font-size: 13px; }
        .summary { display: flex; gap: 20px; margin-bottom: 25px; }
        .summary-box { flex: 1; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb; text-align: center; }
        .summary-box .num { font-size: 24px; font-weight: 700; color: #111; }
        .summary-box .lbl { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }
        .summary-box.red { border-color: #fca5a5; background: #fef2f2; }
        .summary-box.red .num { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; color: #4b5563; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-red { color: #dc2626; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
        .actions { margin-bottom: 20px; }
        .btn { padding: 8px 20px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print / Save PDF</button>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($schoolName); ?></h1>
        <p>Uncleared Alumni Fees Report — Generated <?php echo date('d M Y, h:i A'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-box red">
            <div class="num"><?php echo count($uncleared); ?></div>
            <div class="lbl">Uncleared Alumni</div>
        </div>
        <div class="summary-box">
            <div class="num text-red">Rs. <?php echo number_format($totalOutstanding, 2); ?></div>
            <div class="lbl">Total Outstanding Amount</div>
        </div>
    </div>

    <?php if (empty($uncleared)): ?>
        <p style="text-align:center;padding:40px;color:#9ca3af;">All alumni have cleared their fees.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>GR No</th>
                <th>Student Name</th>
                <th>Father's Name</th>
                <th>Last Class</th>
                <th>Graduation Year</th>
                <th class="text-right">Outstanding (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($uncleared as $u): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td class="font-bold"><?php echo htmlspecialchars($u['gr_no']); ?></td>
                <td><?php echo htmlspecialchars($u['student_name']); ?></td>
                <td><?php echo htmlspecialchars($u['father_name']); ?></td>
                <td><?php echo htmlspecialchars($u['last_class']); ?></td>
                <td><?php echo htmlspecialchars($u['graduation_year']); ?></td>
                <td class="text-right font-bold text-red"><?php echo number_format($u['outstanding'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right font-bold">Total Outstanding:</td>
                <td class="text-right font-bold text-red">Rs. <?php echo number_format($totalOutstanding, 2); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>

    <div class="footer">
        <p>This is a computer-generated report. Powered by AR Software Solution</p>
    </div>
</body>
</html>
