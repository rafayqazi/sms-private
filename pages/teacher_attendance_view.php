<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
$db = new Database();

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$targetTeacherId = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : null;

$reportData = $db->getTeacherAttendanceReport($startDate, $endDate);
$teachers = $db->getAllTeachers();
$teacherNames = [];
foreach ($teachers as $t) {
    $teacherNames[$t['id']] = $t['name'];
}

// Process data for charts or summaries
$summary = [
    'P' => 0,
    'A' => 0,
    'L' => 0
];

// Group by date for the table
$groupedData = [];
// Per-teacher stats for the summary table
$teacherStats = [];
foreach ($teachers as $t) {
    $teacherStats[$t['id']] = ['P' => 0, 'A' => 0, 'L' => 0, 'total' => 0, 'designation' => $t['designation'], 'image' => $t['profile_image']];
}

$individualLogs = [];

foreach ($reportData as $record) {
    $date = $record['date'];
    $tid = $record['teacher_id'];
    
    // Overall Summary (only if no specific teacher is selected or always for the top cards)
    if (isset($summary[$record['status']])) {
        if (!$targetTeacherId || $tid === $targetTeacherId) {
            $summary[$record['status']]++;
        }
    }

    // Group by date for general logs
    if (!isset($groupedData[$date])) {
        $groupedData[$date] = ['P' => 0, 'A' => 0, 'L' => 0, 'total' => 0];
    }
    $groupedData[$date][$record['status']]++;
    $groupedData[$date]['total']++;

    // Staff-wise stats
    if (isset($teacherStats[$tid])) {
        $teacherStats[$tid][$record['status']]++;
        $teacherStats[$tid]['total']++;
    }

    // Individual Logs if teacher selected
    if ($targetTeacherId && $tid === $targetTeacherId) {
        $individualLogs[] = $record;
    }
}
krsort($groupedData);
usort($individualLogs, function($a, $b) { return strcmp($b['date'], $a['date']); });
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-teal-600 to-teal-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl md:text-3xl font-bold">
            <?php echo $targetTeacherId ? htmlspecialchars($teacherNames[$targetTeacherId]) . ' - Attendance' : 'Attendance Reports'; ?>
        </h1>
        <p class="text-teal-100 mt-1">
            <?php echo $targetTeacherId ? 'Individual attendance history and performance' : 'View and analyze staff attendance trends'; ?>
        </p>
    </div>
    <div class="flex gap-3">
        <?php if ($targetTeacherId): ?>
            <a href="teacher_attendance_view.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="w-full md:w-auto bg-teal-500/30 text-white border border-white/30 px-4 py-2 rounded-md hover:bg-teal-500/50 transition duration-300 flex items-center justify-center gap-2 font-medium">
                <i class="fas fa-arrow-left"></i> Overall Report
            </a>
        <?php endif; ?>
        <a href="teacher_attendance.php" class="w-full md:w-auto bg-white/20 backdrop-blur-sm text-white border border-white/30 px-4 py-2 rounded-md hover:bg-white/30 transition duration-300 flex items-center justify-center gap-2 font-medium">
            <i class="fas fa-plus"></i> Mark Attendance
        </a>
    </div>
</div>

<!-- Filters & Summary -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-4">Date Range Filter</h3>
        <form action="" method="GET" class="space-y-4">
            <?php if ($targetTeacherId): ?>
                <input type="hidden" name="teacher_id" value="<?php echo $targetTeacherId; ?>">
            <?php endif; ?>
            <div>
                <label class="block text-xs text-gray-400 mb-1">From</label>
                <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">To</label>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <button type="submit" class="w-full bg-teal-600 text-white py-2 rounded-md text-sm font-bold hover:bg-teal-700 transition-colors">Apply Filter</button>
        </form>
    </div>

    <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?php echo $summary['P']; ?></div>
                <div class="text-xs text-green-600 font-bold uppercase tracking-tighter"><?php echo $targetTeacherId ? 'Days Present' : 'Total Presents'; ?></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fas fa-times"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?php echo $summary['A']; ?></div>
                <div class="text-xs text-red-600 font-bold uppercase tracking-tighter"><?php echo $targetTeacherId ? 'Days Absent' : 'Total Absents'; ?></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fas fa-calendar-minus"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?php echo $summary['L']; ?></div>
                <div class="text-xs text-amber-600 font-bold uppercase tracking-tighter"><?php echo $targetTeacherId ? 'Days on Leave' : 'Total Leaves'; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
    <!-- Staff Performance Summary -->
    <div class="xl:col-span-1">
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 h-full flex flex-col">
            <div class="bg-teal-50 px-6 py-4 border-b border-teal-100 flex justify-between items-center">
                <h2 class="font-bold text-teal-800 flex items-center gap-2">
                    <i class="fas fa-user-tie"></i> Staff-wise Summary
                </h2>
                <div class="text-[10px] bg-teal-600 text-white px-2 py-0.5 rounded-full font-bold uppercase">Period Stats</div>
            </div>
            <div class="p-4 flex-1 overflow-y-auto max-h-[600px] no-scrollbar">
                <div class="space-y-4">
                    <?php if (empty($teacherStats)): ?>
                        <p class="text-gray-400 text-sm text-center py-8 italic">No staff data available</p>
                    <?php else: ?>
                        <?php foreach ($teacherStats as $tid => $stats): ?>
                            <a href="?teacher_id=<?php echo $tid; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                               class="block p-3 rounded-xl border transition-all group <?php echo ($targetTeacherId === $tid) ? 'bg-teal-50 border-teal-300 shadow-sm' : 'bg-gray-50 border-gray-100 hover:border-teal-200 hover:shadow-sm'; ?>">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
                                        <?php if ($stats['image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($stats['image']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="text-teal-600 font-bold"><?php echo substr($teacherNames[$tid], 0, 1); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-gray-800 truncate group-hover:text-teal-700 transition-colors"><?php echo htmlspecialchars($teacherNames[$tid]); ?></div>
                                        <div class="text-[10px] text-gray-500 italic"><?php echo htmlspecialchars($stats['designation']); ?></div>
                                    </div>
                                    <div class="ml-auto">
                                        <?php 
                                        $p = ($stats['total'] > 0) ? round(($stats['P'] / $stats['total']) * 100) : 0;
                                        $colorClass = ($p >= 90) ? 'text-green-600' : ($p >= 70 ? 'text-teal-600' : 'text-red-600');
                                        ?>
                                        <div class="text-right">
                                            <div class="text-xs font-black <?php echo $colorClass; ?>"><?php echo $p; ?>%</div>
                                            <div class="text-[8px] text-gray-400 uppercase font-bold tracking-tighter">Attendance</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-white p-1.5 rounded-md text-center border border-gray-100">
                                        <div class="text-xs font-bold text-green-600"><?php echo $stats['P']; ?></div>
                                        <div class="text-[8px] text-gray-400 uppercase font-bold">Presents</div>
                                    </div>
                                    <div class="bg-white p-1.5 rounded-md text-center border border-gray-100">
                                        <div class="text-xs font-bold text-red-500"><?php echo $stats['A']; ?></div>
                                        <div class="text-[8px] text-gray-400 uppercase font-bold">Absents</div>
                                    </div>
                                    <div class="bg-white p-1.5 rounded-md text-center border border-gray-100">
                                        <div class="text-xs font-bold text-amber-500"><?php echo $stats['L']; ?></div>
                                        <div class="text-[8px] text-gray-400 uppercase font-bold">Leaves</div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Section -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 flex flex-col h-full">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center text-sm">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas <?php echo $targetTeacherId ? 'fa-id-badge' : 'fa-history'; ?>"></i> 
                    <?php echo $targetTeacherId ? 'Personal Attendance History' : 'Daily Attendance Logs'; ?>
                </h2>
                <div class="text-xs text-gray-500">
                    <?php echo $targetTeacherId ? count($individualLogs) . ' records' : count($groupedData) . ' recorded days'; ?>
                </div>
            </div>
            <div class="overflow-x-auto flex-1">
                <?php if ($targetTeacherId): ?>
                    <!-- Individual History Table -->
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-[10px]">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4">Remarks</th>
                                <th class="px-6 py-4">Logged At</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($individualLogs)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No historical records found for this teacher in the selected range.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($individualLogs as $log): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-700"><?php echo date('d M, Y', strtotime($log['date'])); ?></div>
                                            <div class="text-[10px] text-gray-400"><?php echo date('l', strtotime($log['date'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-center">
                                                <?php if ($log['status'] == 'P'): ?>
                                                    <span class="bg-green-100 text-green-700 text-[9px] font-black px-3 py-1 rounded-full border border-green-200 flex items-center gap-1">
                                                        <i class="fas fa-check-circle"></i> PRESENT
                                                    </span>
                                                <?php elseif ($log['status'] == 'A'): ?>
                                                    <span class="bg-red-100 text-red-700 text-[9px] font-black px-3 py-1 rounded-full border border-red-200 flex items-center gap-1">
                                                        <i class="fas fa-times-circle"></i> ABSENT
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-3 py-1 rounded-full border border-amber-200 flex items-center gap-1">
                                                        <i class="fas fa-clock"></i> LEAVE
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500 italic">
                                            <?php echo !empty($log['remarks']) ? htmlspecialchars($log['remarks']) : '---'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-[10px] text-gray-400">
                                            <?php echo date('d M H:i', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="teacher_attendance.php?date=<?php echo $log['date']; ?>" class="text-teal-600 hover:text-teal-800 text-xs font-bold">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <!-- Overall Daily Logs Table -->
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-[10px]">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-center">Presents</th>
                                <th class="px-6 py-4 text-center">Absents</th>
                                <th class="px-6 py-4 text-center">Leaves</th>
                                <th class="px-6 py-4 text-center">Overall</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($groupedData)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">No records found for this period.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($groupedData as $date => $stats): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-700"><?php echo date('d M, Y', strtotime($date)); ?></div>
                                            <div class="text-[10px] text-gray-400"><?php echo date('l', strtotime($date)); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-green-600 font-bold text-sm"><?php echo $stats['P']; ?></span>
                                            <span class="text-gray-300 text-xs">/ <?php echo $stats['total']; ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center text-red-500 font-bold text-sm"><?php echo $stats['A']; ?></td>
                                        <td class="px-6 py-4 text-center text-amber-500 font-bold text-sm"><?php echo $stats['L']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-center">
                                                <?php 
                                                $percent = ($stats['P'] / $stats['total']) * 100;
                                                if ($percent >= 90) $color = 'bg-green-100 text-green-700';
                                                elseif ($percent >= 70) $color = 'bg-teal-100 text-teal-700';
                                                else $color = 'bg-red-100 text-red-700';
                                                ?>
                                                <span class="px-2 py-1 rounded-full text-[9px] font-black <?php echo $color; ?> border">
                                                    <?php echo round($percent); ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="teacher_attendance.php?date=<?php echo $date; ?>" class="text-teal-600 hover:text-teal-800 text-xs font-bold flex items-center justify-end gap-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
