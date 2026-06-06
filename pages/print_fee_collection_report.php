<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/fee_history_report.php';

$db = new Database();
$settings = $db->getSchoolSettings();
$params = [
    'month' => $_GET['month'] ?? '',
    'class' => $_GET['class'] ?? '',
    'stage' => $_GET['stage'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$rows = buildFeeHistoryReport($db, $params);
$reportTitle = getFeeHistoryReportTitle($params);
$schoolName = $settings['school_name'] ?? 'School';
$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo']))
    ? '../' . $settings['school_logo']
    : '../assets/branding/logo.png';

$totPaid = 0;
$totDebt = 0;
foreach ($rows as $r) {
    $totPaid += $r['amount_paid'];
    $totDebt += $r['remaining_debt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Collection Report — <?= htmlspecialchars($reportTitle) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #111; padding: 12px; }
        .header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #0c0784; padding-bottom: 10px; }
        .logo { width: 50px; height: 50px; object-fit: contain; }
        h1 { font-size: 16px; color: #0c0784; }
        .meta { font-size: 10px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #0c0784; font-size: 8px; text-transform: uppercase; }
        td.num { text-align: right; white-space: nowrap; }
        .status-paid { color: #15803d; font-weight: bold; }
        .status-partial { color: #b45309; font-weight: bold; }
        .status-unpaid { color: #b91c1c; font-weight: bold; }
        tfoot td { font-weight: bold; background: #f8fafc; }
        .no-print { margin-bottom: 10px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { size: landscape; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="background:#0c0784;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:bold;">Print / Save as PDF</button>
    </div>

    <div class="header">
        <img src="<?= htmlspecialchars($logoPath) ?>" class="logo" alt="Logo">
        <div>
            <h1><?= htmlspecialchars($schoolName) ?> — Fee Collection Report</h1>
            <div class="meta"><?= htmlspecialchars($reportTitle) ?> · Generated <?= date('d M Y, h:i A') ?> · <?= count($rows) ?> record(s)</div>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <p style="padding:30px;text-align:center;color:#666;">No records found for selected filters.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Stage</th>
                <th>Class</th>
                <th>GR</th>
                <th>Student</th>
                <th>Father</th>
                <th>Month</th>
                <th>Status</th>
                <th>Month Fee</th>
                <th>Arrears</th>
                <th>Total Due</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Method</th>
                <th>Date</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($rows as $r):
                $statusClass = 'status-' . strtolower($r['status']);
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($r['stage']) ?></td>
                <td><?= htmlspecialchars($r['class']) ?></td>
                <td><?= htmlspecialchars($r['gr_no']) ?></td>
                <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['father_name']) ?></td>
                <td><?= htmlspecialchars($r['month_label']) ?></td>
                <td class="<?= $statusClass ?>"><?= htmlspecialchars($r['status']) ?></td>
                <td class="num"><?= number_format($r['month_fee']) ?></td>
                <td class="num"><?= number_format($r['arrears']) ?></td>
                <td class="num"><?= number_format($r['total_due']) ?></td>
                <td class="num"><?= number_format($r['amount_paid']) ?></td>
                <td class="num"><?= number_format($r['remaining_debt']) ?></td>
                <td><?= htmlspecialchars($r['payment_method']) ?></td>
                <td><?= htmlspecialchars($r['payment_date']) ?></td>
                <td><?= htmlspecialchars($r['remarks']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="11" style="text-align:right;">Totals</td>
                <td class="num"><?= number_format($totPaid) ?></td>
                <td class="num"><?= number_format($totDebt) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>

    <script>window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 400); });</script>
</body>
</html>
