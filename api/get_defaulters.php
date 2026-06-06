<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$month = $_GET['month'] ?? date('Y-m');
$stage = $_GET['stage'] ?? '';
$class = $_GET['class'] ?? '';
$search = $_GET['search'] ?? '';

$db = new Database();
$defaulters = $db->getDefaulters($month);

// Filter by search (Name or GR)
if (!empty($search)) {
    $search = strtolower($search);
    $defaulters = array_filter($defaulters, function($s) use ($search) {
        return strpos(strtolower($s['student_name']), $search) !== false || 
               strpos(strtolower($s['gr_no']), $search) !== false;
    });
}

// Filter by class
if (!empty($class)) {
    $defaulters = array_filter($defaulters, function($s) use ($class) {
        return $s['current_class'] === $class;
    });
}

// Filter by stage if requested
if (!empty($stage)) {
    $classes = $db->getClasses();
    $classStageMap = [];
    foreach ($classes as $c) {
        $classStageMap[$c['class_name']] = $c['stage'] ?? 'Elementary';
    }

    $defaulters = array_filter($defaulters, function($s) use ($stage, $classStageMap) {
        $studentClass = $s['current_class'];
        $studentStage = $classStageMap[$studentClass] ?? 'Elementary';
        return $studentStage === $stage;
    });
}
?>
<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
            <tr>
                <th class="px-6 py-4">Student</th>
                <th class="px-6 py-4">Father Name</th>
                <th class="px-6 py-4">Class</th>
                <th class="px-6 py-4">Contact</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (empty($defaulters)): ?>
                <tr class="no-defaulters"><td colspan="6" class="px-6 py-8 text-center text-green-500 font-medium italic">All students have paid for this month!</td></tr>
            <?php else: ?>
                <?php foreach ($defaulters as $s): ?>
                <tr class="hover:bg-red-50 transition">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($s['student_name']); ?></div>
                        <div class="text-[10px] text-gray-500 uppercase">GR: <?php echo $s['gr_no']; ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($s['father_name']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs font-bold">
                            <?php echo htmlspecialchars($s['current_class']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($s['father_contact']); ?></td>
                    <td class="px-6 py-4">
                        <?php if (($s['payment_status'] ?? 'Unpaid') === 'Partial'): ?>
                            <span class="text-amber-600 text-xs font-bold flex flex-col gap-0.5">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-exclamation-triangle"></i> PARTIAL
                                </span>
                                <span class="text-[10px] text-gray-500 font-medium font-mono">Dues: Rs. <?php echo number_format($s['debt']); ?></span>
                            </span>
                        <?php else: ?>
                            <span class="text-red-500 text-xs font-bold flex flex-col gap-0.5">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> UNPAID
                                </span>
                                <span class="text-[10px] text-gray-500 font-medium font-mono">Dues: Rs. <?php echo number_format($s['debt']); ?></span>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="pickStudent('<?php echo $s['gr_no']; ?>', '<?php echo addslashes($s['student_name']); ?>')" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-indigo-700">
                            Collect
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
