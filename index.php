<?php
require_once 'includes/auth_session.php';
require_once 'includes/db.php';
$db = new Database();
$students = $db->readData();

// Filter out Alumni students - they only appear in Alumni tab
$activeStudents = array_filter($students, function($student) {
    return !isset($student['student_status']) || $student['student_status'] !== 'Alumni';
});

$totalStudents = count($activeStudents);
$maleCount = 0;
$femaleCount = 0;
$classes = [];

foreach ($activeStudents as $student) {
    if (isset($student['gender']) && $student['gender'] == 'Male') $maleCount++;
    elseif (isset($student['gender']) && $student['gender'] == 'Female') $femaleCount++;
    
    $class = isset($student['current_class']) ? $student['current_class'] : 'Unknown';
    if (!isset($classes[$class])) $classes[$class] = 0;
    $classes[$class]++;
}
$alumniCount = 0;
foreach ($students as $student) {
    if (isset($student['student_status']) && $student['student_status'] === 'Alumni') {
        $alumniCount++;
    }
}

$teachers = $db->getAllTeachers();
$teacherCount = count($teachers);

$attendanceStats = $db->getAttendanceStats();
$overallStats = $attendanceStats['overall'];
$classStats = $attendanceStats['class_wise'];
$presentCount = $overallStats['Present'];
$attendancePercentage = ($totalStudents > 0) ? round(($presentCount / $totalStudents) * 100, 1) : 0;

// New Dashboard Features Data
$toppers = $db->getToppers(3);
$attendanceToppers = $db->getTopAttendancePerformers(3);
$classPerfStats = $db->getClassPerformanceStats(3);
$birthdays = $db->getBirthdaysToday();

$allInventory = $db->getInventory(['status' => 'Active']);
$lowStockItems = array_filter($allInventory, function($item) {
    return (int)$item['quantity'] < 5;
});
?>

<?php include 'includes/header.php'; ?>

<!-- Update Notification Alert (Premium Style) -->
<?php if (isset($_SESSION['updates_available']) && $_SESSION['updates_available'] === true && (!isset($_SESSION['update_notification_dismissed']) || $_SESSION['update_notification_dismissed'] === false)): ?>
<div id="update-notification-banner" class="mb-8 animate-[slideIn_0.5s_ease-out]">
    <div class="relative bg-white dark:bg-gray-800 rounded-[2rem] p-1 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 px-6 py-4 bg-orange-50/30 dark:bg-orange-950/20 rounded-[1.8rem]">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-orange-200 dark:shadow-none flex-shrink-0 animate-pulse">
                    <i class="fas fa-sync-alt text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 dark:text-gray-100 text-lg leading-tight">System Update Available</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">A new version of the software is ready. Update now to access the latest features and security improvements.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 relative">
                <button onclick="dismissUpdateNotification()" class="absolute -top-12 -right-4 w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-full border border-white dark:border-gray-600 shadow-sm transition-all hover:scale-110" title="Close">
                    <i class="fas fa-times text-xs"></i>
                </button>
                <div class="flex flex-col items-center gap-2">
                    <a href="pages/settings.php?tab=updates" class="bg-orange-600 hover:bg-orange-700 text-white font-black px-8 py-3.5 rounded-2xl transition-all shadow-lg shadow-orange-200 dark:shadow-none hover:-translate-y-0.5 active:scale-95 uppercase tracking-wider text-sm whitespace-nowrap">
                        Update System Now
                    </a>
                    <div class="flex items-center gap-2 text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest bg-orange-100 dark:bg-orange-900/40 px-3 py-1.5 rounded-full border border-orange-200 dark:border-orange-800">
                        <i class="fas fa-hourglass-half"></i>
                        <span>System locking in: <span id="update-timer">60s</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function dismissUpdateNotification() {
    fetch('api/dismiss_update_notification.php')
        .then(() => {
            document.getElementById('update-notification-banner').style.display = 'none';
        });
}

// Mandatory Update Countdown Logic
document.addEventListener('DOMContentLoaded', () => {
    const loginTime = <?php echo $_SESSION['login_time'] ?? time(); ?>;
    const timerDisplay = document.getElementById('update-timer');
    const updateBanner = document.getElementById('update-notification-banner');
    
    if (timerDisplay && updateBanner) {
        function updateTimer() {
            const currentTime = Math.floor(Date.now() / 1000);
            const secondsElapsed = currentTime - loginTime;
            const timeLeft = Math.max(0, 60 - secondsElapsed);
            
            timerDisplay.innerText = timeLeft + 's';
            
            if (timeLeft <= 0) {
                // Time up! Redirect to lock page
                window.location.href = 'pages/update_required.php';
            } else {
                setTimeout(updateTimer, 1000);
            }
        }
        updateTimer();
    }
});
</script>



<div class="bg-gradient-to-r from-primary to-green-900 text-white p-4 md:p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4 relative overflow-hidden">
    <!-- Decorative background element -->
    <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
        <i class="fas fa-graduation-cap text-9xl"></i>
    </div>
    
    <div class="text-center md:text-left relative z-10">
        <h1 class="text-2xl md:text-3xl font-bold">Dashboard</h1>
        <p class="text-green-100 mt-1 flex items-center justify-center md:justify-start gap-2">
            <i class="fas fa-university"></i> <?php echo htmlspecialchars($headerSettings['school_name'] ?? 'School Name'); ?>
        </p>
    </div>
    
    <div class="flex flex-wrap justify-center gap-3 relative z-10">
        <a href="pages/attendance.php" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2 border border-white/20 group">
            <i class="fas fa-calendar-check group-hover:scale-110 transition-transform"></i> Attendance
        </a>
        <a href="pages/results.php" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2 border border-white/20 group">
            <i class="fas fa-poll-h group-hover:scale-110 transition-transform"></i> Add Marks
        </a>
        <a href="pages/settings.php" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2 border border-white/20 group">
            <i class="fas fa-cog group-hover:scale-110 transition-transform"></i> Settings
        </a>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="mb-8 overflow-x-auto pb-2 no-scrollbar">
    <div class="flex gap-4 min-w-max md:min-w-0 md:grid md:grid-cols-4">
        <button onclick="openAdmissionModal()" class="flex-1 bg-gradient-to-br from-indigo-500 to-indigo-600 p-4 rounded-xl text-white shadow-md hover:shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-4 text-left">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <div class="font-bold">Admission</div>
                <div class="text-xs text-indigo-100">New Student</div>
            </div>
        </button>
        <a href="pages/attendance.php" class="flex-1 bg-gradient-to-br from-emerald-500 to-emerald-600 p-4 rounded-xl text-white shadow-md hover:shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <div class="font-bold">Attendance</div>
                <div class="text-xs text-emerald-100">Mark Today</div>
            </div>
        </a>
        <a href="pages/settings.php" class="flex-1 bg-gradient-to-br from-slate-600 to-slate-700 p-4 rounded-xl text-white shadow-md hover:shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                <i class="fas fa-cog"></i>
            </div>
            <div>
                <div class="font-bold">Settings</div>
                <div class="text-xs text-slate-100">System Config</div>
            </div>
        </a>
        <a href="pages/results.php" class="flex-1 bg-gradient-to-br from-amber-500 to-amber-600 p-4 rounded-xl text-white shadow-md hover:shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <div class="font-bold">Exam Result</div>
                <div class="text-xs text-amber-100">Add Marks</div>
            </div>
        </a>
        <a href="pages/inventory.php" class="flex-1 bg-gradient-to-br from-purple-500 to-purple-600 p-4 rounded-xl text-white shadow-md hover:shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                <i class="fas fa-boxes"></i>
            </div>
            <div>
                <div class="font-bold">Inventory</div>
                <div class="text-xs text-purple-100">Manage Stock</div>
            </div>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="pages/students.php" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border-l-4 border-primary hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Total Students</div>
        <div class="text-3xl font-bold text-gray-800 dark:text-gray-100"><?php echo $totalStudents; ?></div>
    </a>
    <a href="pages/students.php?gender=Male" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border-l-4 border-blue-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Male</div>
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo $maleCount; ?></div>
    </a>
    <a href="pages/students.php?gender=Female" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border-l-4 border-pink-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Female</div>
        <div class="text-3xl font-bold text-pink-600 dark:text-pink-400"><?php echo $femaleCount; ?></div>
    </a>
    
    <!-- Row 2 -->
    <a href="pages/alumni.php" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border-l-4 border-purple-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Alumni Students</div>
        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo $alumniCount; ?></div>
    </a>
    <a href="pages/assign_roles.php" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border-l-4 border-amber-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Teaching Staff</div>
        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400"><?php echo $teacherCount; ?></div>
    </a>
    <a href="pages/attendance.php" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border-l-4 border-teal-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-1">Today's Attendance</div>
        <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">
            <?php if ($attendanceStats['is_today']): ?>
                <?php echo $presentCount; ?> <span class="text-lg text-gray-400 dark:text-gray-500 font-normal">(<?php echo $attendancePercentage; ?>%)</span>
            <?php else: ?>
                <span class="text-2xl text-gray-400 dark:text-gray-500">Unmarked</span>
            <?php endif; ?>
        </div>
    </a>
</div>

<div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-4 md:p-6 mt-8 border border-gray-100 dark:border-gray-800">
    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6 border-b dark:border-gray-800 pb-2">Attendance Insights</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Overall Attendance Pie Chart -->
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl shadow-inner border border-gray-100 dark:border-gray-700">
            <h3 class="text-center mb-4 font-semibold text-gray-700 dark:text-gray-300">Overall Attendance Status</h3>
            <?php if (!$attendanceStats['is_today']): ?>
                <div class="relative h-[300px] flex items-center justify-center">
                    <div class="text-center w-full">
                         <i class="fas fa-calendar-times text-gray-400 text-5xl mb-2"></i>
                         <p class="text-gray-600 font-medium text-lg mb-1">Attendance Unmarked for Today</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="relative h-[300px]">
                    <canvas id="overallAttendanceChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Class-wise Attendance Bar Chart -->
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl shadow-inner border border-gray-100 dark:border-gray-700">
            <h3 class="text-center mb-4 font-semibold text-gray-700 dark:text-gray-300">Class-wise Presence</h3>
            <?php if (!$attendanceStats['is_today']): ?>
                <!-- Show Attendance Unmarked Message -->
                <div class="relative h-[300px] flex items-center justify-center">
                    <div class="text-center w-full">
                        <div class="mb-4">
                            <i class="fas fa-calendar-times text-gray-400 text-5xl mb-2"></i>
                            <p class="text-gray-600 font-medium text-lg mb-1">Attendance Unmarked for Today</p>
                            <p class="text-gray-500 text-sm"><?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <div class="max-w-md mx-auto bg-white rounded-lg p-4 shadow-sm max-h-[160px] overflow-y-auto">
                            <p class="text-gray-700 font-semibold mb-3 text-sm sticky top-0 bg-white pb-2 border-b border-gray-100">Classes:</p>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <?php foreach ($classStats as $className => $stats): ?>
                                    <div class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded">
                                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($className); ?></span>
                                        <span class="text-red-500 text-xs font-semibold">Unmarked</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show Bar Chart -->
                <div class="relative h-[300px]">
                    <canvas id="classAttendanceChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
    <!-- Top 3 Toppers Card -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-md overflow-hidden border border-indigo-100 dark:border-gray-800 flex flex-col">
        <div class="bg-indigo-600 dark:bg-indigo-900 p-4 flex items-center justify-between">
            <h2 class="text-white dark:text-gray-100 font-bold flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-300"></i> Top Performers
            </h2>
            <i class="fas fa-award text-indigo-300 dark:text-indigo-400"></i>
        </div>
        
        <!-- Tabs -->
        <div class="flex border-b border-indigo-100 dark:border-gray-800">
            <button onclick="switchTopperTab('academic')" id="tab-academic" class="flex-1 py-3 text-[9px] font-bold uppercase tracking-tighter transition-all border-b-2 border-indigo-600 text-indigo-600 bg-indigo-50/30 dark:bg-indigo-950/20 dark:text-indigo-400">
                Academic
            </button>
            <button onclick="switchTopperTab('attendance')" id="tab-attendance" class="flex-1 py-3 text-[9px] font-bold uppercase tracking-tighter transition-all border-b-2 border-transparent text-gray-400 dark:text-gray-500 hover:text-indigo-500 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                Attendance
            </button>
            <button onclick="switchTopperTab('classwise')" id="tab-classwise" class="flex-1 py-3 text-[9px] font-bold uppercase tracking-tighter transition-all border-b-2 border-transparent text-gray-400 dark:text-gray-500 hover:text-indigo-500 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                Class-wise
            </button>
        </div>

        <div class="p-4 space-y-4 flex-1">
            <!-- Academic Toppers -->
            <div id="content-academic" class="topper-content space-y-4">
                <?php if (empty($toppers)): ?>
                    <p class="text-gray-400 text-center py-6 text-xs italic">No exam data found</p>
                <?php else: ?>
                    <?php foreach ($toppers as $index => $topper): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full border-2 <?php echo ($index == 0) ? 'border-yellow-400' : 'border-gray-200 dark:border-gray-700'; ?> overflow-hidden bg-gray-100 dark:bg-gray-800 flex-shrink-0">
                                    <?php if ($topper['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($topper['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-indigo-400 dark:text-indigo-300 font-bold uppercase text-sm">
                                            <?php echo substr($topper['student_name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center text-[8px] text-white shadow-sm font-bold
                                    <?php echo ($index == 0) ? 'bg-yellow-500' : (($index == 1) ? 'bg-slate-400' : 'bg-amber-600'); ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-800 dark:text-gray-200 truncate text-sm"><?php echo htmlspecialchars($topper['student_name']); ?></div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span class="truncate">Class: <?php echo htmlspecialchars($topper['current_class']); ?></span>
                                    <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 px-1.5 rounded font-bold ml-auto shrink-0"><?php echo $topper['percentage']; ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Attendance Toppers -->
            <div id="content-attendance" class="topper-content hidden space-y-4">
                <?php if (empty($attendanceToppers)): ?>
                    <p class="text-gray-400 text-center py-6 text-xs italic">No attendance records found</p>
                <?php else: ?>
                    <?php foreach ($attendanceToppers as $index => $atopper): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-emerald-50 transition-colors group">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full border-2 <?php echo ($index == 0) ? 'border-emerald-400' : 'border-gray-200'; ?> overflow-hidden bg-gray-100 flex-shrink-0">
                                    <?php if ($atopper['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($atopper['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-emerald-400 font-bold uppercase text-sm">
                                            <?php echo substr($atopper['student_name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center text-[8px] text-white shadow-sm font-bold
                                    <?php echo ($index == 0) ? 'bg-emerald-500' : (($index == 1) ? 'bg-slate-400' : 'bg-amber-600'); ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-800 truncate text-sm group-hover:text-emerald-700 transition-colors"><?php echo htmlspecialchars($atopper['student_name']); ?></div>
                                <div class="text-[10px] text-gray-500 flex items-center gap-2">
                                    <span class="truncate">Class: <?php echo htmlspecialchars($atopper['current_class']); ?></span>
                                    <span class="bg-emerald-100 text-emerald-700 px-1.5 rounded font-bold ml-auto shrink-0"><?php echo $atopper['percentage']; ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-[9px] text-center text-gray-400 pt-2 border-t border-gray-50 italic">Based on overall presence percentage</div>
                <?php endif; ?>
            </div>

            <!-- Class-wise Performance -->
            <div id="content-classwise" class="topper-content hidden space-y-4">
                <?php if (empty($classPerfStats)): ?>
                    <p class="text-gray-400 text-center py-6 text-xs italic">No result data available</p>
                <?php else: ?>
                    <?php foreach ($classPerfStats as $index => $cs): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-blue-50 transition-colors group">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full border-2 <?php echo ($index == 0) ? 'border-primary' : 'border-gray-200'; ?> overflow-hidden bg-gray-100 flex-shrink-0">
                                    <?php if ($cs['topper_img']): ?>
                                        <img src="<?php echo htmlspecialchars($cs['topper_img']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-primary font-bold uppercase text-sm">
                                            <?php echo substr($cs['class_name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center text-[8px] text-white shadow-sm font-bold bg-primary border border-white">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-800 truncate text-sm">Class <?php echo htmlspecialchars($cs['class_name']); ?></div>
                                <div class="text-[10px] text-gray-500 flex flex-col">
                                    <span class="truncate italic">Topper: <?php echo htmlspecialchars($cs['topper_name']); ?> (<?php echo $cs['top_percent']; ?>%)</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-primary font-bold">Avg: <?php echo $cs['avg_percentage']; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-[9px] text-center text-gray-400 pt-2 border-t border-gray-50 italic">Top classes based on average student score</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Inventory Alert Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-red-100">
        <div class="bg-red-500 p-4 flex items-center justify-between">
            <h2 class="text-white font-bold flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
            </h2>
            <span class="bg-white/20 text-white px-2 py-0.5 rounded-full text-xs"><?php echo count($lowStockItems); ?></span>
        </div>
        <div class="p-4">
            <?php if (empty($lowStockItems)): ?>
                <div class="text-center py-6">
                    <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                    <p class="text-gray-500 text-sm">All inventory is well-stocked</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($lowStockItems as $item): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-red-50 border border-red-100">
                            <div>
                                <div class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                <div class="text-[10px] text-gray-500">Remaining Qty: <span class="text-red-600 font-bold"><?php echo $item['quantity']; ?></span></div>
                            </div>
                            <a href="pages/inventory.php" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Birthdays Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-pink-100 flex flex-col">
        <div class="bg-pink-500 p-4 flex items-center justify-between">
            <h2 class="text-white font-bold flex items-center gap-2">
                <i class="fas fa-birthday-cake"></i> Birthdays
            </h2>
            <i class="fas fa-sparkles text-pink-200"></i>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[350px] no-scrollbar">
            <!-- Today Section -->
            <div class="mb-6">
                <h3 class="text-xs font-bold text-pink-600 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span> Today
                </h3>
                <?php if (empty($birthdays['today'])): ?>
                    <p class="text-gray-400 text-xs text-center py-2">No birthdays today</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($birthdays['today'] as $bday): ?>
                            <div class="flex items-center gap-3 bg-pink-50/50 p-2 rounded-lg border border-pink-100/50">
                                <div class="w-10 h-10 rounded-full bg-pink-100 border border-pink-200 overflow-hidden shrink-0">
                                    <?php if ($bday['image']): ?>
                                        <img src="<?php echo htmlspecialchars($bday['image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-pink-500 font-bold">
                                            <?php echo substr($bday['name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($bday['name']); ?></div>
                                    <div class="text-[10px] text-gray-500 flex items-center gap-1">
                                        <i class="fas <?php echo ($bday['type'] == 'teacher') ? 'fa-chalkboard-teacher' : 'fa-user-graduate'; ?>"></i>
                                        <?php echo htmlspecialchars($bday['class']); ?>
                                    </div>
                                    <div class="text-[10px] font-semibold text-pink-600"><?php echo date('d M Y', strtotime($bday['dob'])); ?></div>
                                </div>
                                <div class="ml-auto text-xl">🎉</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Section -->
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Upcoming (Next 15 Days)
                </h3>
                <?php if (empty($birthdays['upcoming'])): ?>
                    <p class="text-gray-400 text-xs text-center py-2 italic">Nothing for now</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($birthdays['upcoming'] as $bday): ?>
                            <div class="flex items-center gap-3 opacity-80 hover:opacity-100 transition-opacity">
                                <div class="w-8 h-8 rounded-full bg-gray-100 border border-gray-200 overflow-hidden shrink-0">
                                    <?php if ($bday['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($bday['image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs font-bold">
                                            <?php echo substr($bday['name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-700 truncate"><?php echo htmlspecialchars($bday['name']); ?></div>
                                    <div class="text-[9px] text-gray-400"><?php echo htmlspecialchars($bday['class']); ?></div>
                                </div>
                                <div class="ml-auto text-[10px] font-bold text-pink-500 bg-pink-50 px-2 py-0.5 rounded">
                                    <?php echo date('d M', strtotime($bday['dob'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php if ($attendanceStats['is_today']): ?>
    // Overall Attendance Chart
    const overallCtx = document.getElementById('overallAttendanceChart').getContext('2d');
    const overallChart = new Chart(overallCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Leave'],
            datasets: [{
                data: [
                    <?php echo $overallStats['Present']; ?>,
                    <?php echo $overallStats['Absent']; ?>,
                    <?php echo $overallStats['Leave']; ?>
                ],
                backgroundColor: ['#10B981', '#EF4444', '#F59E0B'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            onClick: (e, activeEls) => {
                if (activeEls.length > 0) {
                    const index = activeEls[0].index;
                    const label = overallChart.data.labels[index];
                    window.location.href = `pages/attendance_details.php?status=${label}`;
                }
            },
            onHover: (event, chartElement) => {
                event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
            }
        }
    });
    <?php endif; ?>

    // Class-wise Attendance Chart - Only render if attendance is marked for today
    <?php if ($attendanceStats['is_today']): ?>
    const classCtx = document.getElementById('classAttendanceChart').getContext('2d');
    const classStats = <?php echo json_encode($classStats); ?>;
    const classLabels = Object.keys(classStats);
    const classData = classLabels.map(label => classStats[label].Present);
    
    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: classLabels,
            datasets: [{
                label: 'Students Present',
                data: classData,
                backgroundColor: '#3B82F6',
                borderColor: '#2563EB',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const stats = classStats[label];
                            return [
                                `Present: ${stats.Present}`,
                                `Absent: ${stats.Absent}`,
                                `Male: ${stats.Male}`,
                                `Female: ${stats.Female}`
                            ];
                        }
                    }
                }
            },
            onClick: (e, activeEls) => {
                if (activeEls.length > 0) {
                    const index = activeEls[0].index;
                    const label = classLabels[index];
                    window.location.href = `pages/attendance_view.php?class=${encodeURIComponent(label)}`;
                }
            },
            onHover: (event, chartElement) => {
                event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
            }
        }
    });
    <?php endif; ?>
</script>

<div class="bg-white rounded-lg shadow-lg p-4 md:p-6 mt-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-gray-800">Recent Admissions</h2>
        <a href="pages/students.php" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm border border-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-md transition-colors w-full md:w-auto text-center">View All</a>
    </div>
    <?php 
    $recentStudents = array_slice(array_reverse($students), 0, 5);
    ?>
    <!-- Mobile Card View (Visible on small screens) -->
    <div class="md:hidden space-y-4">
        <?php foreach ($recentStudents as $student): ?>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3 pb-3 border-b border-gray-200">
                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                    <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                </div>
                <div>
                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($student['student_name']); ?></div>
                    <div class="text-xs text-gray-500">GR: <?php echo htmlspecialchars($student['gr_no']); ?></div>
                </div>
                <div class="ml-auto">
                     <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        <?php echo htmlspecialchars($student['current_class']); ?>
                    </span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <span class="text-gray-500 text-xs block">Father Name</span>
                    <span class="text-gray-700 font-medium capitalize"><?php echo htmlspecialchars($student['father_name']); ?></span>
                </div>
                <div class="text-right">
                    <span class="text-gray-500 text-xs block">Admission Date</span>
                    <span class="text-gray-700"><?php echo htmlspecialchars($student['admission_date']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop Table View (Hidden on small screens) -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                    <th class="p-4">GR No</th>
                    <th class="p-4">Student Name</th>
                    <th class="p-4">Father Name</th>
                    <th class="p-4">Class</th>
                    <th class="p-4">Admission Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($recentStudents as $student): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="p-4 text-gray-700 font-medium"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                    <td class="p-4 text-gray-800 font-semibold capitalize">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($student['student_name']); ?>
                        </div>
                    </td>
                    <td class="p-4 text-gray-600 capitalize"><?php echo htmlspecialchars($student['father_name']); ?></td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            <?php echo htmlspecialchars($student['current_class']); ?>
                        </span>
                    </td>
                    <td class="p-4 text-gray-500 text-sm"><?php echo htmlspecialchars($student['admission_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Welcome Animation Overlay -->
<?php if (isset($_SESSION['show_welcome_animation']) && $_SESSION['show_welcome_animation']): ?>
<div id="welcome-overlay" class="fixed inset-0 z-[100] bg-gradient-to-br from-indigo-900 to-purple-900 flex items-center justify-center transition-opacity duration-1000">
    <div class="text-center px-4 animate-[scaleIn_0.8s_ease-out]">
        <div class="mb-6">
            <i class="fas fa-school text-6xl text-white mb-4 animate-bounce"></i>
        </div>
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 tracking-tight drop-shadow-lg">
            Welcome to <br> School Management System
        </h1>
        <div class="w-24 h-1 bg-white mx-auto rounded-full mb-6"></div>
        <p class="text-green-300 text-lg md:text-xl font-medium tracking-wide">
            Created By AR Software Solution
        </p>
        <p class="text-indigo-200 text-sm md:text-base mt-2 font-light">
            | Abdul Rafay Qazi |
        </p>
    </div>
</div>

<style>
@keyframes scaleIn {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
<?php 
    unset($_SESSION['show_welcome_animation']);
endif; 
?>

<script>
function switchTopperTab(tab) {
    // Buttons
    const academicBtn = document.getElementById('tab-academic');
    const attendanceBtn = document.getElementById('tab-attendance');
    const classwiseBtn = document.getElementById('tab-classwise');
    
    if (!academicBtn || !attendanceBtn || !classwiseBtn) return;

    academicBtn.classList.remove('border-indigo-600', 'text-indigo-600', 'bg-indigo-50/30');
    academicBtn.classList.add('border-transparent', 'text-gray-400');
    
    attendanceBtn.classList.remove('border-indigo-600', 'text-indigo-600', 'bg-indigo-50/30');
    attendanceBtn.classList.add('border-transparent', 'text-gray-400');

    classwiseBtn.classList.remove('border-indigo-600', 'text-indigo-600', 'bg-indigo-50/30');
    classwiseBtn.classList.add('border-transparent', 'text-gray-400');
    
    // Active Button
    const activeBtn = document.getElementById('tab-' + tab);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-gray-400');
        activeBtn.classList.add('border-indigo-600', 'text-indigo-600', 'bg-indigo-50/30');
    }
    
    // Contents
    document.querySelectorAll('.topper-content').forEach(el => el.classList.add('hidden'));
    const content = document.getElementById('content-' + tab);
    if (content) content.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('welcome-overlay');
    if (overlay) {
        setTimeout(() => {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
            }, 1000);
        }, 3000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
