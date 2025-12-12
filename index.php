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
?>

<?php include 'includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-4 md:p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-2xl md:text-3xl font-bold">Dashboard</h1>
        <p class="text-green-100 mt-1">Welcome to GBPS Ali Bux Jarwar</p>
    </div>
    <a href="pages/student_form.php" class="w-full md:w-auto bg-secondary text-white px-6 py-3 rounded-lg hover:bg-yellow-600 transition-colors shadow-md flex items-center justify-center gap-2 font-semibold">
        <i class="fas fa-plus-circle"></i> New Admission
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="pages/students.php" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-primary hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Total Students</div>
        <div class="text-3xl font-bold text-gray-800"><?php echo $totalStudents; ?></div>
    </a>
    <a href="pages/students.php?gender=Male" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Male</div>
        <div class="text-3xl font-bold text-blue-600"><?php echo $maleCount; ?></div>
    </a>
    <a href="pages/students.php?gender=Female" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-pink-500 hover:scale-105 transition-transform duration-300 cursor-pointer block">
        <div class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Female</div>
        <div class="text-3xl font-bold text-pink-600"><?php echo $femaleCount; ?></div>
    </a>
</div>

<?php
$attendanceStats = $db->getAttendanceStats();
$overallStats = $attendanceStats['overall'];
$classStats = $attendanceStats['class_wise'];
?>

<div class="bg-white rounded-lg shadow-lg p-4 md:p-6 mt-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">Attendance Insights</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Overall Attendance Pie Chart -->
        <div class="bg-gray-50 p-4 rounded-xl shadow-inner border border-gray-100">
            <h3 class="text-center mb-4 font-semibold text-gray-700">Overall Attendance Status</h3>
            <div class="relative h-[300px]">
                <canvas id="overallAttendanceChart"></canvas>
            </div>
        </div>

        <!-- Class-wise Attendance Bar Chart -->
        <div class="bg-gray-50 p-4 rounded-xl shadow-inner border border-gray-100">
            <h3 class="text-center mb-4 font-semibold text-gray-700">Class-wise Presence</h3>
            <?php if (!$attendanceStats['is_today']): ?>
                <!-- Show Attendance Unmarked Message -->
                <div class="relative h-[300px] flex items-center justify-center">
                    <div class="text-center w-full">
                        <div class="mb-4">
                            <i class="fas fa-calendar-times text-gray-400 text-5xl mb-2"></i>
                            <p class="text-gray-600 font-medium text-lg mb-1">Attendance Unmarked for Today</p>
                            <p class="text-gray-500 text-sm"><?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <div class="max-w-md mx-auto bg-white rounded-lg p-4 shadow-sm">
                            <p class="text-gray-700 font-semibold mb-3 text-sm">Classes:</p>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('welcome-overlay');
    
    // Play sound if desired (optional, browser policies might block)
    
    // Hide after 3 seconds
    setTimeout(() => {
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.remove();
        }, 1000); // Wait for fade out
    }, 3000);
});
</script>
<?php 
    // Unset the session variable so it doesn't show again on refresh
    unset($_SESSION['show_welcome_animation']); 
?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
