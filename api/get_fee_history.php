<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$month = $_GET['month'] ?? '';
$gr_no = $_GET['gr_no'] ?? '';
$class_filter = $_GET['class'] ?? '';

$db = new Database();
$filters = [];
if ($month) $filters['month'] = $month;
if ($gr_no) $filters['gr_no'] = $gr_no;

$history = $db->getFeeCollections($filters);

// Fetch all students
$all_students = $db->readData();

// Build display list
$display_list = [];
$is_student_history = $gr_no && !$month && !$class_filter;

if ($month && !$gr_no) {
    // Status View: Show all students in the filtered scope and their status for that specific month
    foreach ($all_students as $s) {
        $student_gr = $s['gr_no'];
        $student_class = $s['current_class'];
        
        // Apply class filters
        if ($class_filter && $student_class !== $class_filter) continue;
        if (isset($s['student_status']) && $s['student_status'] === 'Alumni') continue;

        // Find payment for this month (if month provided)
        $payment = null;
        foreach ($history as $h) {
            if ($h['gr_no'] == $student_gr) {
                if (!$month || $h['month_for'] == $month) {
                    $payment = $h;
                    break;
                }
            }
        }

        $display_list[] = [
            'gr_no' => $student_gr,
            'student_name' => $s['student_name'],
            'class' => $student_class,
            'payment' => $payment
        ];
    }
} else {
    // Transaction Mode: Show specific transactions (useful for full student history)
    foreach ($history as $h) {
        // Find student details
        $s_name = 'Unknown';
        $s_class = 'N/A';
        foreach($all_students as $s) {
            if ($s['gr_no'] == $h['gr_no']) { 
                $s_name = $s['student_name']; 
                $s_class = $s['current_class']; 
                break; 
            }
        }
        
        // Apply class filter if present
        if ($class_filter && $s_class !== $class_filter) continue;

        $display_list[] = [
            'gr_no' => $h['gr_no'],
            'student_name' => $s_name,
            'class' => $s_class,
            'payment' => $h
        ];
    }
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
                <th class="px-6 py-4">Method/Date</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            <?php if (empty($display_list)): ?>
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">No matching records found.</td></tr>
            <?php else: ?>
                <?php foreach ($display_list as $row): ?>
                <?php $p = $row['payment']; $isPaid = !empty($p); ?>
                <tr class="hover:bg-gray-50 transition group">
                    <td class="px-6 py-4">
                        <?php if ($isPaid): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 w-fit">
                                <i class="fas fa-check-circle"></i> Paid
                            </span>
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
                        <?php else: ?>
                            <span class="text-gray-300 italic text-sm">Pending</span>
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
                                <a href="print_receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="p-2 inline-flex items-center justify-center bg-gray-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition shadow-sm" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
                                <button onclick="window.selectStudentWithMonth('<?php echo $p['gr_no']; ?>', '<?php echo $p['month_for']; ?>')" class="p-2 inline-flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm" title="Modify Entry">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="window.confirmDeletion('<?php echo $p['id']; ?>', '<?php echo date('F Y', strtotime($p['month_for'] . '-01')); ?>')" class="p-2 inline-flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition shadow-sm" title="Delete Entry">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <button onclick="pickStudent('<?php echo $row['gr_no']; ?>', '<?php echo addslashes($row['student_name']); ?>')" class="px-3 py-1.5 bg-indigo-600 text-white text-[10px] font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center gap-1.5 ml-auto">
                                <i class="fas fa-hand-holding-usd"></i> Collect
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

