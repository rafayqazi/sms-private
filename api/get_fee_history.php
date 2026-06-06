<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

require_once '../includes/fee_history_report.php';

$month = $_GET['month'] ?? '';
$gr_no = $_GET['gr_no'] ?? '';
$class_filter = $_GET['class'] ?? '';
$stage_filter = $_GET['stage'] ?? '';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'paid_first';

$db = new Database();
$is_student_history = $gr_no && !$month && !$class_filter && !$stage_filter;

$reportRows = buildFeeHistoryReport($db, [
    'month' => $month,
    'class' => $class_filter,
    'stage' => $stage_filter,
    'search' => $search,
    'gr_no' => $gr_no
]);

$display_list = array_map(function ($r) {
    return [
        'gr_no' => $r['gr_no'],
        'student_name' => $r['student_name'],
        'class' => $r['class'],
        'payment' => $r['payment'] ?? null,
        '_report' => $r
    ];
}, $reportRows);

if (!$is_student_history && count($display_list) > 1) {
    $feeStructure = $db->getFeeStructure();
    $getSortTier = function($row) use ($feeStructure) {
        $p = $row['payment'];
        if (empty($p)) return 0;

        $classFees = $feeStructure[$row['class']] ?? ['monthly_fee' => 0];
        $assignedMonthly = (float)$classFees['monthly_fee'];
        $due_tuition = (isset($p['tuition_fee']) && $p['tuition_fee'] !== '' && (float)$p['tuition_fee'] > 0)
            ? (float)$p['tuition_fee'] : $assignedMonthly;
        $expected = $due_tuition
            + (float)($p['admission_fee'] ?? 0)
            + (float)($p['exam_fee'] ?? 0)
            + (float)($p['other_fee'] ?? 0)
            - (float)($p['discount'] ?? 0);
        $debt = max(0.0, $expected - (float)$p['amount_paid']);

        return $debt > 0 ? 1 : 2;
    };

    usort($display_list, function($a, $b) use ($sort, $getSortTier) {
        $tierA = $getSortTier($a);
        $tierB = $getSortTier($b);

        if ($sort === 'unpaid_first') {
            if ($tierA !== $tierB) return $tierA - $tierB;
        } else {
            if ($tierA !== $tierB) return $tierB - $tierA;
        }

        return strcasecmp($a['student_name'], $b['student_name']);
    });
}

?>
<?php if ($is_student_history && !empty($display_list)): ?>
<div class="px-6 py-4 bg-gray-50 flex flex-col md:flex-row justify-between items-center border-b border-gray-100 gap-3">
    <div class="flex items-center gap-2">
        <i class="fas fa-history text-indigo-500"></i>
        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Full Payment History</span>
    </div>
    <a href="print_fee_history.php?gr_no=<?php echo $gr_no; ?>" target="_blank" class="text-xs bg-indigo-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-indigo-700 transition font-bold shadow-md no-print">
        <i class="fas fa-file-pdf"></i> Download PDF / Print All
    </a>
</div>
<?php endif; ?>

<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
            <tr>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Student</th>
                <th class="px-6 py-4">For Month</th>
                <th class="px-6 py-4">Amount</th>
                <th class="px-6 py-4">Remarks</th>
                <th class="px-6 py-4">Method/Date</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            <?php if (empty($display_list)): ?>
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">No matching records found.</td></tr>
            <?php else: ?>
                <?php foreach ($display_list as $row): ?>
                <?php 
                $p = $row['payment']; 
                $isPaid = !empty($p); 
                $debt = (float)($row['_report']['remaining_debt'] ?? 0);
                if ($isPaid && $debt <= 0) {
                    $feeStructure = $db->getFeeStructure();
                    $classFees = $feeStructure[$row['class']] ?? ['monthly_fee' => 0];
                    $assignedMonthly = (float)$classFees['monthly_fee'];
                    $due_tuition = (isset($p['tuition_fee']) && $p['tuition_fee'] !== '' && (float)$p['tuition_fee'] > 0)
                                   ? (float)$p['tuition_fee']
                                   : $assignedMonthly;
                    $expected = $due_tuition + (float)($p['admission_fee'] ?? 0) + (float)($p['exam_fee'] ?? 0) + (float)($p['other_fee'] ?? 0) - (float)($p['discount'] ?? 0);
                    $debt = max(0.0, $expected - (float)$p['amount_paid']);
                }
                ?>
                <tr class="hover:bg-gray-50 transition group">
                    <td class="px-6 py-4">
                        <?php if ($isPaid): ?>
                            <?php if ($debt > 0): ?>
                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 w-fit">
                                    <i class="fas fa-exclamation-triangle"></i> Partial
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 w-fit">
                                    <i class="fas fa-check-circle"></i> Paid
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 w-fit">
                                <i class="fas fa-clock"></i> Unpaid
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div onclick="window.showStudentHistory('<?php echo $row['gr_no']; ?>', '<?php echo addslashes($row['student_name']); ?>')" class="font-bold text-gray-800 hover:text-indigo-600 cursor-pointer transition flex items-center gap-2 group/name">
                            <?php echo htmlspecialchars($row['student_name']); ?>
                            <i class="fas fa-history text-[10px] text-gray-300 group-hover/name:text-indigo-400 opacity-0 group-hover/name:opacity-100 transition"></i>
                        </div>
                        <div class="text-[10px] text-gray-500 font-medium">GR: <?php echo $row['gr_no']; ?> | <?php echo $row['class']; ?></div>
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-700">
                        <?php 
                        $m = $isPaid ? $p['month_for'] : ($month ?: date('Y-m'));
                        echo date('F Y', strtotime($m . "-01")); 
                        ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($isPaid): ?>
                            <div class="font-bold text-gray-900">Rs. <?php echo number_format($p['amount_paid']); ?></div>
                            <?php if($p['discount'] > 0): ?>
                                <div class="text-[10px] text-red-500 font-medium">Disc: Rs. <?php echo number_format($p['discount']); ?></div>
                            <?php endif; ?>
                            <?php if($debt > 0): ?>
                                <div class="text-[10px] text-amber-600 font-bold font-mono">Dues: Rs. <?php echo number_format($debt); ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-300 italic text-sm">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-500 max-w-[220px]">
                        <?php if ($isPaid && !empty($p['notes'])): ?>
                            <span class="italic leading-relaxed block whitespace-pre-line"><?php echo htmlspecialchars($p['notes']); ?></span>
                        <?php else: ?>
                            <span class="text-gray-300">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?php if ($isPaid): ?>
                            <div class="font-medium"><?php echo $p['payment_method']; ?></div>
                            <div class="text-[10px] text-gray-400"><?php echo $p['payment_date']; ?></div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <?php if ($isPaid): ?>
                            <div class="flex items-center gap-2 justify-end">
                                <button onclick="window.showStudentHistory('<?php echo $row['gr_no']; ?>', '<?php echo addslashes($row['student_name']); ?>')" class="p-2 inline-flex items-center justify-center bg-teal-50 text-teal-600 rounded-lg hover:bg-teal-600 hover:text-white transition shadow-sm" title="View Full History">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="print_receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="p-2 inline-flex items-center justify-center bg-gray-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition shadow-sm" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
                                <button onclick="window.selectStudentWithMonth('<?php echo $p['gr_no']; ?>', '<?php echo addslashes($row['student_name']); ?>', '<?php echo $p['month_for']; ?>')" class="p-2 inline-flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm" title="Modify Entry">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="window.confirmDeletion('<?php echo $p['id']; ?>', '<?php echo date('F Y', strtotime($p['month_for'] . '-01')); ?>')" class="p-2 inline-flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition shadow-sm" title="Delete Entry">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-2 justify-end">
                                <button onclick="window.showStudentHistory('<?php echo $row['gr_no']; ?>', '<?php echo addslashes($row['student_name']); ?>')" class="p-2 inline-flex items-center justify-center bg-teal-50 text-teal-600 rounded-lg hover:bg-teal-600 hover:text-white transition shadow-sm" title="View Full History">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="pickStudent('<?php echo $row['gr_no']; ?>', '<?php echo addslashes($row['student_name']); ?>')" class="px-3 py-1.5 bg-indigo-600 text-white text-[10px] font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center gap-1.5">
                                    <i class="fas fa-hand-holding-usd"></i> Collect
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

