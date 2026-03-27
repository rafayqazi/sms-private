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

if (($month || $class_filter) && !$gr_no) {
    // Status View: Show all students in the filtered scope and their status for that month
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
        foreach($all_students as $s) if($s['gr_no'] == $h['gr_no']) { $s_name = $s['student_name']; $s_class = $s['current_class']; break; }
        
        $display_list[] = [
            'gr_no' => $h['gr_no'],
            'student_name' => $s_name,
            'class' => $s_class,
            'payment' => $h
        ];
    }
}

?>
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
                        <?php echo $isPaid ? htmlspecialchars($p['month_for']) : ($month ?: 'Current'); ?>
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
                            <a href="print_receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="p-2 inline-flex items-center justify-center bg-gray-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition shadow-sm" title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </a>
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

