<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Ensure user is teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$db = new Database();
// Use the teacher's ID from session
$teacher_id = $_SESSION['teacher_id'];
$teacher = $db->getTeacher($teacher_id);
if (!$teacher) {
    die("Teacher record not found.");
}

// Assigned classes
$assignedStr = $teacher['assigned_classes'] ?? '';
$assignedClasses = [];
if (!empty($assignedStr)) {
    // Split by comma and clean up
    $rawClasses = explode(',', $assignedStr);
    foreach ($rawClasses as $c) {
        $clean = trim($c);
        if ($clean !== '') {
            $assignedClasses[] = $clean;
        }
    }
}

// Get Students in these classes
$allStudents = $db->readData();
$classStudents = []; // Grouped by class
$totalStudents = 0;

// Gather all unique classes the teacher has access to check stats
foreach ($allStudents as $student) {
    // Filter active and non-alumni students
    if (isset($student['student_status']) && $student['student_status'] === 'Alumni') {
        continue;
    }
    
    $studentClass = trim($student['current_class'] ?? '');
    if (in_array($studentClass, $assignedClasses)) {
        if (!isset($classStudents[$studentClass])) {
            $classStudents[$studentClass] = [];
        }
        $classStudents[$studentClass][] = $student;
        $totalStudents++;
    }
}

// Get Salary Data for this teacher
$salaryFile = __DIR__ . '/../data/salary_payments.csv';
$teacherSalaries = [];
$latestSalary = null;

if (file_exists($salaryFile)) {
    if (($handle = fopen($salaryFile, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 0, ",");
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // id,teacher_id,month,base_salary,deduction,net_salary,payment_date,notes,created_at
            if (count($row) >= 6 && $row[1] == $teacher_id) {
                $salData = [
                    'id' => $row[0],
                    'month' => $row[2],
                    'base_salary' => $row[3],
                    'deduction' => $row[4],
                    'net_salary' => $row[5],
                    'payment_date' => $row[6] ?? '',
                    'notes' => $row[7] ?? '',
                    'created_at' => $row[8] ?? ''
                ];
                $teacherSalaries[] = $salData;
            }
        }
        fclose($handle);
    }
}

// Sort salaries by payment_date descending
usort($teacherSalaries, function($a, $b) {
    return strtotime($b['payment_date']) - strtotime($a['payment_date']);
});

if (!empty($teacherSalaries)) {
    $latestSalary = $teacherSalaries[0];
}

$currentMonth = date('Y-m');
$salaryReleasedThisMonth = false;
foreach ($teacherSalaries as $sal) {
    if ($sal['month'] === $currentMonth) {
        $salaryReleasedThisMonth = true;
        break;
    }
}

?>
<?php include '../includes/header.php'; ?>

<!-- Teacher Dashboard Header -->
<div class="bg-gradient-to-r from-violet-600 to-fuchsia-700 text-white p-6 md:p-8 rounded-2xl shadow-lg mb-8 relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-6">
    <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4 pointer-events-none">
        <i class="fas fa-chalkboard-teacher text-9xl"></i>
    </div>
    
    <div class="z-10 flex flex-col md:flex-row items-center gap-6">
        <?php 
        $cleanedImgPath = !empty($teacher['profile_image']) ? ltrim(str_replace('../', '', $teacher['profile_image']), '/') : '';
        if (!empty($cleanedImgPath) && file_exists('../' . $cleanedImgPath)): 
        ?>
            <div class="w-24 h-24 md:w-28 md:h-28 rounded-full border-4 border-white/20 shadow-xl overflow-hidden shrink-0 bg-white/10">
                <img src="../<?php echo htmlspecialchars($cleanedImgPath); ?>?v=<?= time(); ?>" alt="Profile" class="w-full h-full object-cover">
            </div>
        <?php else: ?>
            <div class="w-24 h-24 md:w-28 md:h-28 rounded-full border-4 border-white/20 shadow-xl bg-white/10 flex items-center justify-center shrink-0">
                <i class="fas fa-user-tie text-5xl text-white/80 drop-shadow-sm"></i>
            </div>
        <?php endif; ?>
        
        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black tracking-tight drop-shadow-sm mb-1">Welcome, <?php echo htmlspecialchars($teacher['name']); ?>!</h1>
            <p class="text-violet-200 font-medium flex items-center justify-center md:justify-start gap-2">
                <i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
            </p>
        </div>
    </div>
    
    <div class="flex gap-4 z-10 w-full md:w-auto overflow-x-auto pb-2 md:pb-0 hide-scrollbar justify-center">
        <a href="teacher_profile.php?id=<?php echo $teacher_id; ?>" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-3 rounded-xl transition-all shadow-sm flex items-center gap-3 backdrop-blur-sm whitespace-nowrap font-semibold">
            <i class="fas fa-id-badge"></i> View Profile
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Stat Card: Classes Assigned -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-5 hover:shadow-md transition-shadow">
        <div class="w-16 h-16 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-3xl shrink-0 border border-blue-100 dark:border-blue-800/50">
            <i class="fas fa-chalkboard"></i>
        </div>
        <div>
            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Assigned Classes</p>
            <h3 class="text-3xl font-black text-gray-800 dark:text-gray-100"><?php echo count($assignedClasses); ?></h3>
        </div>
    </div>
    
    <!-- Stat Card: Total Students -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-5 hover:shadow-md transition-shadow">
        <div class="w-16 h-16 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl shrink-0 border border-emerald-100 dark:border-emerald-800/50">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div>
            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Total Students</p>
            <h3 class="text-3xl font-black text-gray-800 dark:text-gray-100"><?php echo $totalStudents; ?></h3>
        </div>
    </div>
    
    <!-- Stat Card: Salary Status -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-5 hover:shadow-md transition-shadow">
        <?php if ($salaryReleasedThisMonth): ?>
            <div class="w-16 h-16 rounded-xl bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center text-3xl shrink-0 border border-green-100 dark:border-green-800/50">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Salary Status</p>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-black text-green-600 dark:text-green-400 leading-tight">Released</h3>
                    <span class="text-xs bg-green-100 dark:bg-green-900/60 text-green-700 dark:text-green-400 px-2 py-0.5 rounded-md font-bold text-center">This Month</span>
                </div>
            </div>
        <?php else: ?>
            <div class="w-16 h-16 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-3xl shrink-0 border border-amber-100 dark:border-amber-800/50 animate-pulse">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Salary Status</p>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-black text-amber-600 dark:text-amber-400 leading-tight">Pending</h3>
                    <span class="text-xs bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-md font-bold text-center">This Month</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Middle/Left Col: Assigned Classes Details -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="bg-indigo-50 dark:bg-indigo-900/30 px-6 py-4 border-b border-indigo-100 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-black text-indigo-900 dark:text-indigo-300 text-lg flex items-center gap-3">
                    <i class="fas fa-users text-indigo-500"></i> Assigned Classes & Students
                </h2>
                <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full border border-indigo-200"><?php echo count($assignedClasses); ?> Classes</span>
            </div>
            
            <div class="p-6">
                <?php if (empty($assignedClasses)): ?>
                    <div class="text-center py-10 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <i class="fas fa-info-circle text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <h3 class="text-gray-600 dark:text-gray-400 font-bold text-lg">No classes currently assigned</h3>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Please contact administration to assign classes.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($assignedClasses as $className): ?>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden hover:border-indigo-300 dark:hover:border-indigo-500/50 transition-colors">
                                <div class="bg-gray-50 dark:bg-gray-800/80 px-4 py-3 flex items-center justify-between cursor-pointer group" onclick="this.nextElementSibling.classList.toggle('hidden')">
                                    <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                        <i class="fas fa-chalkboard text-gray-400 group-hover:text-indigo-500 transition-colors"></i> 
                                        <span><?php echo htmlspecialchars($className); ?></span>
                                    </h3>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 px-2 py-1 rounded-md border border-gray-200 dark:border-gray-700 shadow-sm">
                                            <?php echo isset($classStudents[$className]) ? count($classStudents[$className]) : 0; ?> Students
                                        </span>
                                        <i class="fas fa-chevron-down text-gray-400 group-hover:text-indigo-500 transition-colors"></i>
                                    </div>
                                </div>
                                <div class="hidden bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                                    <?php if (!isset($classStudents[$className]) || empty($classStudents[$className])): ?>
                                        <div class="p-4 text-center text-gray-400 dark:text-gray-500 text-sm italic">
                                            No active students in this class.
                                        </div>
                                    <?php else: ?>
                                        <div class="max-h-64 overflow-y-auto no-scrollbar">
                                            <table class="w-full text-left text-sm">
                                                <thead class="bg-gray-50 dark:bg-gray-800/50 sticky top-0 text-gray-500 dark:text-gray-400 text-xs uppercase font-bold tracking-wider">
                                                    <tr>
                                                        <th class="px-4 py-2 border-b dark:border-gray-700">GR No</th>
                                                        <th class="px-4 py-2 border-b dark:border-gray-700">Student Name</th>
                                                        <th class="px-4 py-2 border-b dark:border-gray-700">Father Name</th>
                                                        <th class="px-4 py-2 border-b dark:border-gray-700">Contact</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                    <?php foreach ($classStudents[$className] as $student): ?>
                                                        <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 transition-colors">
                                                            <td class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                                                            <td class="px-4 py-3">
                                                                <div class="flex items-center gap-3">
                                                                    <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-black shrink-0">
                                                                        <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                                                    </div>
                                                                    <span class="font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($student['student_name']); ?></span>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 capitalize"><?php echo htmlspecialchars($student['father_name']); ?></td>
                                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                                <?php echo !empty($student['father_contact']) ? htmlspecialchars($student['father_contact']) : '<span class="italic text-gray-300 dark:text-gray-600">N/A</span>'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Right Col: Salary Summary -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="bg-emerald-50 dark:bg-emerald-900/30 px-6 py-4 border-b border-emerald-100 dark:border-gray-800">
                <h2 class="font-black text-emerald-800 dark:text-emerald-400 text-lg flex items-center gap-3">
                    <i class="fas fa-file-invoice-dollar text-emerald-500"></i> Salary Details
                </h2>
            </div>
            
            <div class="p-6">
                <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700 border-dashed">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Current Base Salary</p>
                    <div class="text-3xl font-black text-gray-800 dark:text-gray-100 flex items-baseline gap-1">
                        <span class="text-sm text-gray-400 font-bold tracking-normal leading-none pr-1">Rs.</span> 
                        <?php echo number_format((float)($teacher['salary'] ?? 0)); ?>
                    </div>
                </div>

                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200">Recent Payment History</h3>
                    <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-1 rounded font-bold"><?php echo count($teacherSalaries); ?> Records</span>
                </div>

                <?php if (empty($teacherSalaries)): ?>
                    <div class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <i class="fas fa-file-invoice text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                        <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">No salary history found</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
                        <?php foreach (array_slice($teacherSalaries, 0, 5) as $salary): ?>
                            <div class="bg-white dark:bg-gray-800 border-2 <?php echo ($salary === $latestSalary) ? 'border-emerald-100 dark:border-emerald-900/50 shadow-sm' : 'border-gray-100 dark:border-gray-700'; ?> rounded-xl p-4 transition-all">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-black text-gray-800 dark:text-gray-200">
                                        <?php echo date('F Y', strtotime($salary['month'] . '-01')); ?>
                                        <?php if ($salary === $latestSalary): ?>
                                            <span class="ml-2 text-[10px] bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">Latest</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-black text-emerald-600 dark:text-emerald-400">Rs. <?php echo number_format((float)$salary['net_salary']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4 text-xs mt-2">
                                    <div class="text-gray-500"><span class="text-gray-400">Base:</span> <?php echo number_format((float)$salary['base_salary']); ?></div>
                                    <?php if ((float)$salary['deduction'] > 0): ?>
                                        <div class="text-red-500 font-medium"><span class="text-red-400">Ded:</span> -<?php echo number_format((float)$salary['deduction']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-3 pt-3 border-t border-gray-50 dark:border-gray-700 flex justify-between items-center text-[10px] text-gray-400 font-medium">
                                    <span><i class="fas fa-calendar-check mr-1"></i> Paid on <?php echo date('d M Y', strtotime($salary['payment_date'])); ?></span>
                                    <?php if (!empty($salary['notes'])): ?>
                                        <span class="italic truncate max-w-[120px]" title="<?php echo htmlspecialchars($salary['notes']); ?>"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($salary['notes']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Complaint Form -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="bg-red-50 dark:bg-red-900/30 px-6 py-4 border-b border-red-100 dark:border-gray-800">
                <h2 class="font-black text-red-800 dark:text-red-400 text-lg flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-red-500"></i> Lodge a Complaint
                </h2>
            </div>
            
            <div class="p-6">
                <form id="complaintForm" onsubmit="submitComplaint(event)">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Message to Administration</label>
                    <div class="relative">
                        <textarea id="complaintMessage" rows="4" oninput="handleMention(event)" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-4 resize-none text-sm" placeholder="Type '@' to mention a student. Describe your issue or complaint here..." required></textarea>
                        
                        <!-- Mention Dropdown -->
                        <div id="mentionDropdown" class="absolute z-20 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl mt-1 hidden max-h-48 overflow-y-auto custom-scrollbar"></div>
                    </div>
                    
                    <button type="submit" id="complaintSubmitBtn" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-xl transition-colors flex items-center justify-center gap-2 shadow-lg shadow-red-200 dark:shadow-none text-sm">
                        <i class="fas fa-paper-plane"></i> Send Complaint
                    </button>
                    <!-- Success/Error Message Container -->
                    <div id="complaintResponse" class="mt-3 text-sm font-bold text-center hidden"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar for inner components */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #475569;
}
</style>

<script>
let isMentioning = false;
let mentionStartIndex = -1;

function handleMention(e) {
    const input = e.target;
    const val = input.value;
    const cursorPosition = input.selectionStart;
    
    // Check text right before the cursor
    const textBeforeCursor = val.substring(0, cursorPosition);
    const lastAtSymbol = textBeforeCursor.lastIndexOf('@');
    
    if (lastAtSymbol !== -1) {
        // Ensure '@' is at start of string or preceded by space/newline
        if (lastAtSymbol === 0 || /\s/.test(textBeforeCursor.charAt(lastAtSymbol - 1))) {
            const query = textBeforeCursor.substring(lastAtSymbol + 1);
            // If the search string has no spaces (user is still typing it)
            if (!/\s/.test(query)) {
                isMentioning = true;
                mentionStartIndex = lastAtSymbol;
                fetchStudentsForMention(query);
                return;
            }
        }
    }
    
    closeMentionDropdown();
}

function fetchStudentsForMention(query) {
    fetch(`../api/search_students_for_mention.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            const dropdown = document.getElementById('mentionDropdown');
            dropdown.innerHTML = '';
            if (data.length > 0) {
                data.forEach(student => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/40 cursor-pointer text-sm border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors flex items-center justify-between';
                    
                    div.innerHTML = `
                        <div class="flex flex-col truncate pr-2">
                            <span class="font-bold text-gray-800 dark:text-gray-200 truncate">${student.name}</span>
                            <span class="text-xs text-gray-500 font-medium">Class: ${student.class}</span>
                        </div>
                        <span class="text-[10px] font-black tracking-widest text-indigo-500 bg-indigo-50 px-2 py-1 rounded-md uppercase shrink-0">GR: ${student.gr_no}</span>
                    `;
                    
                    div.onmousedown = function(e) { 
                        e.preventDefault(); // prevent input blur
                        insertMention(student.name); 
                    };
                    dropdown.appendChild(div);
                });
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        })
        .catch(() => closeMentionDropdown());
}

function insertMention(studentName) {
    const input = document.getElementById('complaintMessage');
    const val = input.value;
    
    // Split the text exactly around the `@` string we evaluated
    const textBeforeMention = val.substring(0, mentionStartIndex);
    const cursorPosition = input.selectionStart;
    const textAfterCursor = val.substring(cursorPosition);
    
    // Insert mention properly formatted without spaces
    const mentionText = '@' + studentName.replace(/\s+/g, '') + ' ';
    
    input.value = textBeforeMention + mentionText + textAfterCursor;
    
    closeMentionDropdown();
    input.focus();
    
    // Set correct cursor position
    const newPos = textBeforeMention.length + mentionText.length;
    input.setSelectionRange(newPos, newPos);
}

function closeMentionDropdown() {
    isMentioning = false;
    document.getElementById('mentionDropdown').classList.add('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id !== 'complaintMessage') {
        closeMentionDropdown();
    }
});

function submitComplaint(e) {
    e.preventDefault();
    const btn = document.getElementById('complaintSubmitBtn');
    const msgInput = document.getElementById('complaintMessage');
    const responseDiv = document.getElementById('complaintResponse');
    const msg = msgInput.value.trim();
    
    if (!msg) return;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    responseDiv.className = 'mt-3 text-sm font-bold text-center hidden';
    
    const formData = new FormData();
    formData.append('message', msg);
    
    fetch('../api/send_teacher_message.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        responseDiv.classList.remove('hidden');
        if (data.success) {
            responseDiv.classList.add('text-emerald-600', 'dark:text-emerald-400');
            responseDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            msgInput.value = '';
            setTimeout(() => { responseDiv.classList.add('hidden'); }, 5000);
        } else {
            responseDiv.classList.add('text-red-600', 'dark:text-red-400');
            responseDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Failed to send complaint.');
        }
    })
    .catch(err => {
        responseDiv.classList.remove('hidden');
        responseDiv.classList.add('text-red-600', 'dark:text-red-400');
        responseDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Complaint';
    });
}
</script>

<?php include '../includes/footer.php'; ?>
