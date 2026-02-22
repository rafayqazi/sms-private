<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$month = $_GET['month'] ?? '';
$gr_no = $_GET['gr_no'] ?? '';

$db = new Database();
$filters = [];
if ($month) $filters['month'] = $month;
if ($gr_no) $filters['gr_no'] = $gr_no;

$history = $db->getFeeCollections($filters);

// Also need student names as they aren't in collections csv
$students = $db->readData();
$studentMap = [];
foreach ($students as $s) {
    $studentMap[$s['gr_no']] = $s['student_name'];
}

?>
<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
            <tr>
                <th class="px-6 py-4">Transaction ID</th>
                <th class="px-6 py-4">Student</th>
                <th class="px-6 py-4">For Month</th>
                <th class="px-6 py-4">Amount Paid</th>
                <th class="px-6 py-4">Discount</th>
                <th class="px-6 py-4">Date</th>
                <th class="px-6 py-4">Method</th>
                <th class="px-6 py-4">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (empty($history)): ?>
                <tr><td colspan="8" class="px-6 py-8 text-center text-gray-400">No records found.</td></tr>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-xs font-mono">#<?php echo $h['id']; ?></td>
                    <td class="px-6 py-4 tracking-tight">
                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($studentMap[$h['gr_no']] ?? 'Unknown'); ?></div>
                        <div class="text-[10px] text-gray-500">GR: <?php echo $h['gr_no']; ?></div>
                    </td>
                    <td class="px-6 py-4 font-semibold text-indigo-600"><?php echo $h['month_for']; ?></td>
                    <td class="px-6 py-4 font-bold">Rs. <?php echo number_format($h['amount_paid'], 2); ?></td>
                    <td class="px-6 py-4 text-red-500 text-sm">-<?php echo number_format($h['discount'], 2); ?></td>
                    <td class="px-6 py-4 text-sm"><?php echo $h['payment_date']; ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-gray-100 rounded text-[10px] uppercase font-bold text-gray-600">
                            <?php echo $h['payment_method']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="print_receipt.php?id=<?php echo $h['id']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
