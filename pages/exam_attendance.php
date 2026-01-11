<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();
// Admin/Super Admin can see all classes, others restricted
$allowedClasses = getAssignedClasses();

// Filters/Inputs
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'print'; // 'print' or 'online'
$examName = isset($_GET['exam_name']) ? $_GET['exam_name'] : '';
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$time = isset($_GET['time']) ? $_GET['time'] : '';

// Handle Online Attendance Submission
$successMsg = '';
$defaultSubjects = ['ENG', 'MATH', 'Social Studies', 'G.Science', 'MT', 'Islamyat', 'NMT'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendanceData = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    $scheduleData = isset($_POST['schedule']) ? $_POST['schedule'] : [];
    $examName = isset($_POST['exam_name']) ? $_POST['exam_name'] : $examName;

    // Refactor attendance data to be [Subject => [StudentId => Status]]
    $subjectWiseAttendance = [];
    foreach ($attendanceData as $studentId => $subjects) {
        foreach ($subjects as $subj => $status) {
            $subjectWiseAttendance[$subj][$studentId] = $status;
        }
    }

    if (!empty($scheduleData)) {
        // Iterate over ALL subjects present in the schedule (Defaults + Dynamic)
        foreach ($scheduleData as $subj => $info) {
            $sDate = isset($info['date']) ? $info['date'] : '';
            $sTime = isset($info['time']) ? $info['time'] : '';
            
            // Get Attendance for this subject
            $stuStatuses = isset($subjectWiseAttendance[$subj]) ? $subjectWiseAttendance[$subj] : [];
            
            // We must pass the $time to saveExamAttendance
            // If there are no students, we still want to save the schedule effectively?
            // The current DB implementation saves schedule embedded in attendance rows.
            // If there are NO students, we can't save the schedule row.
            // But usually there are students.
            if (!empty($stuStatuses)) {
                $db->saveExamAttendance($examName, $selectedClass, $subj, $sDate, $stuStatuses, $sTime);
            } elseif (!empty($sDate) || !empty($sTime)) {
                // If no students but we have schedule, maybe create a dummy record or just skip?
                // For now, assuming students exist.
            }
        }
        $successMsg = "Attendance and Schedule saved successfully!";
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 max-w-[95%] mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-clipboard-list text-teal-600"></i> Examination Attendance
        </h2>

        <!-- Tab Navigation -->
        <div class="flex border-b mb-6 overflow-x-auto">
            <a href="?tab=print<?php echo $selectedClass ? "&class=$selectedClass" : ""; ?>" 
               class="py-2 px-4 font-semibold whitespace-nowrap <?php echo $activeTab === 'print' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-teal-500'; ?> transition-colors">
                Print Blank Sheets
            </a>
            <a href="?tab=online<?php echo $selectedClass ? "&class=$selectedClass" : ""; ?>" 
               class="py-2 px-4 font-semibold whitespace-nowrap <?php echo $activeTab === 'online' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-teal-500'; ?> transition-colors">
                Online Attendance
            </a>
            <a href="?tab=view<?php echo $selectedClass ? "&class=$selectedClass" : ""; ?>" 
               class="py-2 px-4 font-semibold whitespace-nowrap <?php echo $activeTab === 'view' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-teal-500'; ?> transition-colors">
                View Attendance
            </a>
        </div>

        <?php if ($successMsg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $successMsg; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="GET" class="space-y-6" id="classForm">
            <input type="hidden" name="tab" value="<?php echo $activeTab; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Class Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
                    <select name="class" id="classSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors" required onchange="this.form.submit()">
                        <option value="">Select Class</option>
                        <?php foreach ($allowedClasses as $class): ?>
                            <option value="<?php echo $class; ?>" <?php echo $selectedClass === $class ? 'selected' : ''; ?>>
                                Class <?php echo $class; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <?php if ($selectedClass): ?>
            <?php if ($activeTab === 'print'): ?>
                <!-- Print Blank Sheets Section -->
                <div class="mt-8 border-t pt-6" id="generateSection">
                    <form action="print_exam_attendance.php" method="GET" target="_blank" class="space-y-6">
                        <input type="hidden" name="class" value="<?php echo htmlspecialchars($selectedClass); ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Examination Name</label>
                            <input type="text" name="exam_name" placeholder="e.g. Mid Term Examination 2025" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Datesheet Input Section -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                                <span>Datesheet Configuration</span>
                                <button type="button" onclick="addSubjectRow()" class="text-sm bg-teal-600 text-white px-3 py-1 rounded hover:bg-teal-700 transition">
                                    <i class="fas fa-plus"></i> Add Subject
                                </button>
                            </h3>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left" id="datesheetTable">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2">Subject</th>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Day</th>
                                            <th class="px-3 py-2">Time</th>
                                            <th class="px-3 py-2 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <!-- Dynamic Rows will be added here -->
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Select a date to automatically calculate the day.</p>
                        </div>

                        <div class="bg-teal-50 border border-teal-100 rounded-lg p-4 flex items-start gap-3">
                            <i class="fas fa-info-circle text-teal-500 mt-1"></i>
                            <div>
                                <h4 class="font-medium text-teal-800">Note</h4>
                                <p class="text-sm text-teal-600 mt-1">
                                    All subjects added here will appear as <strong>columns</strong> on a single consolidated attendance sheet (Landscape format).
                                </p>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-file-signature"></i> Generate Attendance Sheets
                        </button>
                    </form>
                </div>

                <script>
                    const DEFAULT_SUBJECTS = ['ENG', 'MATH', 'Social Studies', 'G.Science', 'MT', 'Islamyat', 'NMT'];

                    function addSubjectRow(subjectName = '', dateValue = '', timeValue = '') {
                        const tbody = document.querySelector('#datesheetTable tbody');
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-gray-50';
                        
                        let dayValue = '';
                        if (dateValue) {
                            const d = new Date(dateValue);
                            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            dayValue = days[d.getDay()];
                        }

                        row.innerHTML = `
                            <td class="px-2 py-2">
                                <input type="text" name="subjects[]" value="${subjectName}" placeholder="Subject Name" required class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 bg-transparent">
                            </td>
                            <td class="px-2 py-2">
                                <input type="date" name="dates[]" value="${dateValue}" required onchange="handleDateChange(this)" class="date-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 bg-transparent">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" name="days[]" value="${dayValue}" readonly placeholder="Day" class="day-input w-full px-2 py-1 bg-gray-100 border rounded cursor-not-allowed text-gray-500">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" name="times[]" value="${timeValue}" placeholder="e.g. 9:00 AM" required onchange="handleTimeChange(this)" class="time-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 bg-transparent">
                            </td>
                            <td class="px-2 py-2 text-center">
                                <button type="button" onclick="this.closest('tr').remove()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    }

                    function handleDateChange(input) {
                        calculateDay(input);
                        const allDateInputs = document.querySelectorAll('.date-input');
                        if (input === allDateInputs[0]) {
                            autoFillDates(input.value);
                        }
                    }

                    function handleTimeChange(input) {
                        const allTimeInputs = document.querySelectorAll('.time-input');
                        if (input === allTimeInputs[0]) {
                            const newValue = input.value;
                            for(let i = 1; i < allTimeInputs.length; i++) {
                                allTimeInputs[i].value = newValue;
                            }
                        }
                    }

                    function calculateDay(dateInput) {
                        const date = new Date(dateInput.value);
                        const row = dateInput.closest('tr');
                        const dayInput = row.querySelector('.day-input');
                        if (!isNaN(date.getTime())) {
                            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            dayInput.value = days[date.getDay()];
                        } else {
                            dayInput.value = '';
                        }
                    }

                    function autoFillDates(startDateStr) {
                        if (!startDateStr) return;
                        const startDate = new Date(startDateStr);
                        const allDateInputs = document.querySelectorAll('.date-input');
                        let currentDate = new Date(startDate);
                        for(let i = 1; i < allDateInputs.length; i++) {
                            currentDate.setDate(currentDate.getDate() + 1);
                            if (currentDate.getDay() === 0) {
                                currentDate.setDate(currentDate.getDate() + 1);
                            }
                            const yyyy = currentDate.getFullYear();
                            const mm = String(currentDate.getMonth() + 1).padStart(2, '0');
                            const dd = String(currentDate.getDate()).padStart(2, '0');
                            const formattedDate = `${yyyy}-${mm}-${dd}`;
                            allDateInputs[i].value = formattedDate;
                            calculateDay(allDateInputs[i]);
                        }
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        const tbody = document.querySelector('#datesheetTable tbody');
                        if (tbody && tbody.children.length === 0) {
                            DEFAULT_SUBJECTS.forEach(sub => addSubjectRow(sub));
                        }
                    });
                </script>

            <?php elseif ($activeTab === 'view'): ?>
                <!-- View Attendance Section -->
                <div class="mt-8 border-t pt-6">
                    <?php 
                    $viewExamName = isset($_GET['view_exam_name']) ? $_GET['view_exam_name'] : '';
                    ?>

                    <?php if (!$viewExamName): ?>
                        <h3 class="text-xl font-bold text-gray-800 mb-6">Select Examination to View</h3>
                        <?php 
                        $availableExams = $db->getExamsByClass($selectedClass);
                        if (empty($availableExams)): 
                        ?>
                            <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg border border-yellow-200">
                                No examination records found for <strong>Class <?php echo htmlspecialchars($selectedClass); ?></strong>.
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($availableExams as $exName): 
                                    $exSchedule = $db->getExamSchedule($exName, $selectedClass); // Just to check if data exists really
                                ?>
                                    <a href="?tab=view&class=<?php echo urlencode($selectedClass); ?>&view_exam_name=<?php echo urlencode($exName); ?>" 
                                       class="block bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-teal-500 transition-all group">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="p-2 bg-teal-50 text-teal-600 rounded-lg group-hover:bg-teal-600 group-hover:text-white transition-colors">
                                                <i class="fas fa-file-alt text-xl"></i>
                                            </div>
                                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-teal-500"></i>
                                        </div>
                                        <h4 class="font-bold text-gray-800 text-lg group-hover:text-teal-700 transition"><?php echo htmlspecialchars($exName); ?></h4>
                                        <p class="text-sm text-gray-500 mt-1">Click to view detailed attendance</p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Detailed View -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <a href="?tab=view&class=<?php echo urlencode($selectedClass); ?>" class="text-gray-500 hover:text-gray-700 transition">
                                    <i class="fas fa-arrow-left text-lg"></i>
                                </a>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($viewExamName); ?></h3>
                                    <p class="text-sm text-gray-500">Attendance Record for Class <?php echo htmlspecialchars($selectedClass); ?></p>
                                </div>
                            </div>
                            
                            <?php 
                            $printUrl = "print_online_attendance.php?class=" . urlencode($selectedClass) . "&exam_name=" . urlencode($viewExamName); 
                            ?>
                            <a href="<?php echo htmlspecialchars($printUrl); ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                                <i class="fas fa-print"></i> Print Sheet
                            </a>
                        </div>
                        
                        <?php
                        $students = $db->getStudentsByClass($selectedClass);
                        $savedSchedule = $db->getExamSchedule($viewExamName, $selectedClass);
                        $savedSubjects = array_keys($savedSchedule);
                        $defaultSubjects = ['ENG', 'MATH', 'Social Studies', 'G.Science', 'MT', 'Islamyat', 'NMT'];
                        $allViewSubjects = array_unique(array_merge($defaultSubjects, $savedSubjects));

                        // Pre-fetch attendance
                        $attendanceMap = [];
                        foreach ($allViewSubjects as $sub) {
                            $subAttendance = $db->getExamAttendance($viewExamName, $selectedClass, $sub);
                            foreach ($subAttendance as $stuId => $status) {
                                $attendanceMap[$stuId][$sub] = $status;
                            }
                        }
                        ?>

                        <!-- Schedule Info -->
                         <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 shadow-sm mb-6">
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-calendar-check text-teal-600"></i> Schedule Summary</h4>
                            <div class="flex flex-wrap gap-3">
                                <?php foreach ($allViewSubjects as $sub): 
                                    $info = isset($savedSchedule[$sub]) ? $savedSchedule[$sub] : ['date'=>'','time'=>''];
                                    if(empty($info['date']) && empty($info['time'])) continue;
                                    $d = $info['date'] ? date('d M', strtotime($info['date'])) : 'No Date';
                                    $t = $info['time'] ? $info['time'] : 'No Time';
                                ?>
                                    <div class="bg-white border text-sm px-3 py-2 rounded shadow-sm">
                                        <span class="font-bold text-gray-700 block text-xs uppercase"><?php echo $sub; ?></span>
                                        <span class="text-teal-600 font-semibold"><?php echo $d; ?></span> <span class="text-gray-400">|</span> <span class="text-gray-600"><?php echo $t; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Read-Only Matrix -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-center border-collapse">
                                    <thead class="bg-gray-100 text-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 font-bold text-left border-r w-16 sticky left-0 bg-gray-100 z-20">GR No</th>
                                            <th class="px-4 py-3 font-bold text-left border-r min-w-[150px] sticky left-16 bg-gray-100 z-20">Student Name</th>
                                            <?php foreach ($allViewSubjects as $sub): ?>
                                                <th class="px-2 py-3 font-bold border-r min-w-[70px]">
                                                    <div class="text-xs uppercase text-gray-500"><?php echo htmlspecialchars($sub); ?></div>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y relative">
                                        <?php if (empty($students)): ?>
                                            <tr><td colspan="<?php echo count($allViewSubjects) + 2; ?>" class="p-8 text-center text-gray-500">No students found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $student): ?>
                                                <tr class="hover:bg-gray-50 group">
                                                    <td class="px-4 py-2 font-mono text-left font-bold border-r sticky left-0 bg-white group-hover:bg-gray-50 z-10"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                                                    <td class="px-4 py-2 text-left uppercase border-r sticky left-16 bg-white group-hover:bg-gray-50 z-10"><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                    
                                                    <?php foreach ($allViewSubjects as $sub): ?>
                                                        <?php 
                                                        $status = isset($attendanceMap[$student['id']][$sub]) ? $attendanceMap[$student['id']][$sub] : '-'; 
                                                        $statusColor = 'text-gray-400';
                                                        if($status === 'P') $statusColor = 'text-teal-700 font-bold bg-teal-50';
                                                        if($status === 'A') $statusColor = 'text-red-700 font-bold bg-red-50';
                                                        if($status === 'L') $statusColor = 'text-yellow-700 font-bold bg-yellow-50';
                                                        ?>
                                                        <td class="px-1 py-1 border-r">
                                                            <div class="w-full py-1 rounded text-center <?php echo $statusColor; ?>">
                                                                <?php echo $status; ?>
                                                            </div>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'online'): ?>
                <!-- Online Attendance Section -->
                <div class="mt-8 border-t pt-6">
                    <!-- Exam Name Selection -->
                    <form action="" method="GET" class="mb-8 flex gap-4 items-end">
                        <input type="hidden" name="tab" value="online">
                        <input type="hidden" name="class" value="<?php echo htmlspecialchars($selectedClass); ?>">
                        
                        <div class="flex-grow max-w-md">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Examination Name</label>
                            <input type="text" name="exam_name" value="<?php echo htmlspecialchars($examName); ?>" placeholder="e.g. Annual Examination 2025" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                        </div>
                        <button type="submit" class="bg-teal-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-teal-700 transition shadow-sm">
                            Load Exam Data
                        </button>
                    </form>

                    <?php if ($examName): ?>
                        <?php 
                        $students = $db->getStudentsByClass($selectedClass);
                        $savedSchedule = $db->getExamSchedule($examName, $selectedClass);
                        
                        // Merge default subjects with any extra subjects found in saved schedule
                        $savedSubjects = array_keys($savedSchedule);
                        $allSubjects = array_unique(array_merge($defaultSubjects, $savedSubjects));

                        // Pre-fetch attendance for all subjects
                        $attendanceMap = [];
                        foreach ($allSubjects as $sub) {
                            $subAttendance = $db->getExamAttendance($examName, $selectedClass, $sub);
                            foreach ($subAttendance as $stuId => $status) {
                                $attendanceMap[$stuId][$sub] = $status;
                            }
                        }
                        ?>
                        
                        <form action="" method="POST" class="space-y-8" id="attendanceForm">
                            <input type="hidden" name="exam_name" value="<?php echo htmlspecialchars($examName); ?>">
                            
                            <!-- 1. Exam Schedule Configuration -->
                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 shadow-sm">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-calendar-alt text-teal-600"></i> Exam Schedule
                                    </h3>
                                    <button type="button" onclick="addOnlineSubject()" class="text-sm bg-teal-600 text-white px-3 py-1.5 rounded hover:bg-teal-700 transition shadow-sm font-semibold">
                                        <i class="fas fa-plus mr-1"></i> Add Subject
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mb-4">Set the Date and Time for each paper. Use the first row date to autofill consecutive dates (skipping Sundays).</p>
                                
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left" id="onlineScheduleTable">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-200">
                                            <tr>
                                                <th class="px-4 py-2 rounded-tl-lg">Subject</th>
                                                <th class="px-4 py-2">Date</th>
                                                <th class="px-4 py-2">Day</th>
                                                <th class="px-4 py-2 rounded-tr-lg">Time</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200 border-b border-l border-r border-gray-200">
                                            <?php foreach ($allSubjects as $index => $sub): 
                                                $savedDate = isset($savedSchedule[$sub]['date']) ? $savedSchedule[$sub]['date'] : ''; 
                                                $savedTime = isset($savedSchedule[$sub]['time']) ? $savedSchedule[$sub]['time'] : ''; 
                                                $dayStr = '';
                                                if ($savedDate) {
                                                    $dayStr = date('l', strtotime($savedDate));
                                                }
                                            ?>
                                                <tr>
                                                    <td class="px-4 py-2 font-medium text-gray-800">
                                                        <?php echo htmlspecialchars($sub); ?>
                                                        <input type="hidden" name="schedule[<?php echo htmlspecialchars($sub); ?>][subject]" value="<?php echo htmlspecialchars($sub); ?>">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="date" name="schedule[<?php echo htmlspecialchars($sub); ?>][date]" value="<?php echo $savedDate; ?>" 
                                                            class="online-date-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 text-sm"
                                                            onchange="handleOnlineDateChange(this, <?php echo $index; ?>)">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="text" readonly class="online-day-input w-full bg-gray-50 px-2 py-1 text-gray-500 text-sm border-0" value="<?php echo $dayStr; ?>">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="text" name="schedule[<?php echo htmlspecialchars($sub); ?>][time]" value="<?php echo $savedTime; ?>" 
                                                            class="online-time-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 text-sm" placeholder="e.g. 9:00 AM"
                                                            onchange="handleOnlineTimeChange(this, <?php echo $index; ?>)">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 2. Student Attendance Matrix -->
                            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                                    <h3 class="text-lg font-bold text-gray-800">
                                        <i class="fas fa-users text-teal-600 mr-2"></i> Student Attendance
                                    </h3>
                                    <div class="text-xs text-gray-500">
                                        <span class="inline-block w-3 h-3 bg-teal-100 border border-teal-500 mr-1"></span> Present (P)
                                        <span class="inline-block w-3 h-3 bg-red-100 border border-red-500 ml-2 mr-1"></span> Absent (A)
                                        <span class="inline-block w-3 h-3 bg-yellow-100 border border-yellow-500 ml-2 mr-1"></span> Leave (L)
                                    </div>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-center border-collapse" id="attendanceMatrix">
                                        <thead class="bg-gray-100 text-gray-700">
                                            <tr>
                                                <th class="px-4 py-3 font-bold text-left border-r w-16 sticky left-0 bg-gray-100 z-20">GR No</th>
                                                <th class="px-4 py-3 font-bold text-left border-r min-w-[150px] sticky left-16 bg-gray-100 z-20">Student Name</th>
                                                <?php foreach ($allSubjects as $sub): ?>
                                                    <th class="px-2 py-3 font-bold border-r min-w-[70px] subject-col-<?php echo md5($sub); ?>">
                                                        <div class="text-xs uppercase text-gray-500 mb-1"><?php echo htmlspecialchars($sub); ?></div>
                                                    </th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y relative">
                                            <?php if (empty($students)): ?>
                                                <tr><td colspan="<?php echo count($allSubjects) + 2; ?>" class="p-8 text-center text-gray-500">No students found in Class <?php echo $selectedClass; ?>.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($students as $student): ?>
                                                    <tr class="hover:bg-gray-50 group">
                                                        <td class="px-4 py-2 font-mono text-left font-bold border-r sticky left-0 bg-white group-hover:bg-gray-50 z-10"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                                                        <td class="px-4 py-2 text-left uppercase border-r sticky left-16 bg-white group-hover:bg-gray-50 z-10"><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                        
                                                        <?php foreach ($allSubjects as $sub): ?>
                                                            <?php 
                                                                $status = isset($attendanceMap[$student['id']][$sub]) ? $attendanceMap[$student['id']][$sub] : 'P'; 
                                                                $bgClass = $status === 'A' ? 'bg-red-50 text-red-700' : ($status === 'L' ? 'bg-yellow-50 text-yellow-700' : 'text-teal-700');
                                                            ?>
                                                            <td class="px-1 py-1 border-r subject-col-<?php echo md5($sub); ?>">
                                                                <select name="attendance[<?php echo $student['id']; ?>][<?php echo htmlspecialchars($sub); ?>]" 
                                                                        class="w-full text-sm font-bold border-gray-200 rounded py-1 pl-2 focus:ring-1 focus:ring-teal-500 cursor-pointer <?php echo $bgClass; ?>"
                                                                        onchange="updateColor(this)">
                                                                    <option value="P" class="text-teal-700 bg-white" <?php echo $status === 'P' ? 'selected' : ''; ?>>P</option>
                                                                    <option value="A" class="text-red-700 bg-white" <?php echo $status === 'A' ? 'selected' : ''; ?>>A</option>
                                                                    <option value="L" class="text-yellow-700 bg-white" <?php echo $status === 'L' ? 'selected' : ''; ?>>L</option>
                                                                </select>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>


                            <!-- Actions -->
                            <div class="sticky bottom-4 z-30 pt-4">
                                <div class="bg-white/90 backdrop-blur-md p-4 rounded-xl border border-gray-200 shadow-lg flex flex-wrap justify-between items-center gap-4">
                                     <div class="text-sm text-gray-500 hidden sm:block">
                                        <i class="fas fa-info-circle mr-1"></i> Changes appear immediately on printouts after saving.
                                     </div>
                                     <div class="flex gap-4 w-full sm:w-auto">
                                        <?php
                                        $printUrl = "print_online_attendance.php?class=" . urlencode($selectedClass) . "&exam_name=" . urlencode($examName);
                                        ?>
                                        <a href="<?php echo htmlspecialchars($printUrl); ?>" target="_blank" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                        <button type="submit" name="save_attendance" class="flex-1 sm:flex-none bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                                            <i class="fas fa-save"></i> Save Attendance
                                        </button>
                                     </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Add Subject Modal -->
                <div id="addSubjectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3 text-center">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Add New Subject</h3>
                            <div class="mt-2 px-7 py-3">
                                <input type="text" id="newSubjectName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500" placeholder="Enter Subject Name (e.g. Sindhi)">
                                <p id="subjectError" class="text-red-500 text-xs mt-1 hidden">Subject name is required.</p>
                            </div>
                            <div class="items-center px-4 py-3">
                                <button id="confirmAddSubject" class="px-4 py-2 bg-teal-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    Add Subject
                                </button>
                                <button onclick="closeAddSubjectModal()" class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function addOnlineSubject() {
                        document.getElementById('addSubjectModal').classList.remove('hidden');
                        document.getElementById('newSubjectName').value = '';
                        document.getElementById('subjectError').classList.add('hidden');
                        document.getElementById('newSubjectName').focus();
                    }

                    function closeAddSubjectModal() {
                        document.getElementById('addSubjectModal').classList.add('hidden');
                    }

                    document.getElementById('confirmAddSubject').addEventListener('click', function() {
                        const subjectName = document.getElementById('newSubjectName').value.trim();
                        if (!subjectName) {
                            document.getElementById('subjectError').classList.remove('hidden');
                            return;
                        }
                        executeAddSubject(subjectName);
                        closeAddSubjectModal();
                    });

                    // Allow Enter key to submit
                    document.getElementById('newSubjectName').addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            document.getElementById('confirmAddSubject').click();
                        }
                    });

                    function executeAddSubject(subjectName) {
                        // Check if subject already exists
                        const inputs = document.querySelectorAll('input[name^="schedule["]');
                        for (let input of inputs) {
                            if (input.value === subjectName && input.type === 'hidden') {
                                alert("Subject already exists!");
                                return;
                            }
                        }

                        // 1. Add to Schedule Table
                        const scheduleBody = document.querySelector('#onlineScheduleTable tbody');
                        const rowCount = scheduleBody.children.length;
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                            <td class="px-4 py-2 font-medium text-gray-800">
                                ${subjectName}
                                <input type="hidden" name="schedule[${subjectName}][subject]" value="${subjectName}">
                            </td>
                            <td class="px-4 py-2">
                                <input type="date" name="schedule[${subjectName}][date]" class="online-date-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 text-sm" onchange="handleOnlineDateChange(this, ${rowCount})">
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" readonly class="online-day-input w-full bg-gray-50 px-2 py-1 text-gray-500 text-sm border-0">
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" name="schedule[${subjectName}][time]" class="online-time-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-teal-500 text-sm" placeholder="e.g. 9:00 AM" onchange="handleOnlineTimeChange(this, ${rowCount})">
                            </td>
                        `;
                        scheduleBody.appendChild(newRow);

                        // 2. Add to Attendance Matrix
                        const matrix = document.querySelector('#attendanceMatrix');
                        const thead = matrix.querySelector('thead tr');
                        const tbody = matrix.querySelector('tbody');

                        // Header Column
                        const th = document.createElement('th');
                        th.className = "px-2 py-3 font-bold border-r min-w-[70px]";
                        th.innerHTML = `<div class="text-xs uppercase text-gray-500 mb-1">${subjectName}</div>`;
                        thead.appendChild(th);

                        // Body Columns (for each student)
                        const rows = tbody.querySelectorAll('tr');
                        rows.forEach(row => {
                            // Extract student ID from an existing select in this row
                            const existingSelect = row.querySelector('select');
                            let studentId = '';
                            if (existingSelect) {
                                // name format: attendance[123][ENG]
                                const match = existingSelect.name.match(/attendance\[(\d+)\]/);
                                if (match) studentId = match[1];
                            }

                            if (studentId) {
                                const td = document.createElement('td');
                                td.className = "px-1 py-1 border-r";
                                td.innerHTML = `
                                    <select name="attendance[${studentId}][${subjectName}]" 
                                            class="w-full text-sm font-bold border-gray-200 rounded py-1 pl-2 focus:ring-1 focus:ring-teal-500 cursor-pointer text-teal-700"
                                            onchange="updateColor(this)">
                                        <option value="P" class="text-teal-700 bg-white" selected>P</option>
                                        <option value="A" class="text-red-700 bg-white">A</option>
                                        <option value="L" class="text-yellow-700 bg-white">L</option>
                                    </select>
                                `;
                                row.appendChild(td);
                            }
                        });
                    }

                    function handleOnlineDateChange(input, index) {
                        calculateDayOnline(input);
                        // If it's the first date input, trigger autofill
                        if (index === 0) {
                            autoFillOnlineDates(input.value);
                        }
                    }

                    function calculateDayOnline(dateInput) {
                        const date = new Date(dateInput.value);
                        const row = dateInput.closest('tr');
                        const dayInput = row.querySelector('.online-day-input');
                        if (!isNaN(date.getTime())) {
                            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            dayInput.value = days[date.getDay()];
                        } else {
                            dayInput.value = '';
                        }
                    }

                    function autoFillOnlineDates(startDateStr) {
                        if (!startDateStr) return;
                        const allDateInputs = document.querySelectorAll('.online-date-input');
                        let currentDate = new Date(startDateStr);
                        
                        // Start from index 1 (the second subject)
                        for(let i = 1; i < allDateInputs.length; i++) {
                            currentDate.setDate(currentDate.getDate() + 1);
                            // Skip Sunday
                            if (currentDate.getDay() === 0) {
                                currentDate.setDate(currentDate.getDate() + 1);
                            }
                            
                            const yyyy = currentDate.getFullYear();
                            const mm = String(currentDate.getMonth() + 1).padStart(2, '0');
                            const dd = String(currentDate.getDate()).padStart(2, '0');
                            
                            allDateInputs[i].value = `${yyyy}-${mm}-${dd}`;
                            calculateDayOnline(allDateInputs[i]);
                        }
                    }

                    function handleOnlineTimeChange(input, index) {
                        // Autofill time for all subsequent inputs if it's the first one
                        if (index === 0) {
                            const allTimeInputs = document.querySelectorAll('.online-time-input');
                            const val = input.value;
                            for(let i = 1; i < allTimeInputs.length; i++) {
                                allTimeInputs[i].value = val;
                            }
                        }
                    }

                    function updateColor(select) {
                        const val = select.value;
                        select.classList.remove('bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700', 'text-teal-700', 'bg-white');
                        
                        if (val === 'A') {
                            select.classList.add('bg-red-50', 'text-red-700');
                        } else if (val === 'L') {
                            select.classList.add('bg-yellow-50', 'text-yellow-700');
                        } else {
                            select.classList.add('text-teal-700');
                        }
                    }
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
