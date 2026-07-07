<?php
require_once '../includes/parent_auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$parent_cnic = getLoggedInParentCnic();
$children = $db->getParentChildrenByCnic($parent_cnic);
$settings = $db->getSchoolSettings();

// Prepare GR numbers for ID card generator
$gr_nos_arr = array_column($children, 'gr_no');
$gr_nos_string = implode(',', $gr_nos_arr);

$parent_name = getLoggedInParentName();

// Fetch Notices & Announcements
$notices = $db->getParentNotices($parent_cnic);
// Calculate Metrics for Dashboard
$total_attendance_days = 0;
$total_present_days = 0;
$latest_results = [];

foreach ($children as $child) {
    // Attendance
    $history = $db->getStudentAttendanceHistory($child['id']);
    foreach ($history as $record) {
        $total_attendance_days++;
        if ($record['status'] === 'P') $total_present_days++;
    }
    
    // Results
    $child_results = $db->getStudentResults($child['id']);
    if (!empty($child_results)) {
        usort($child_results, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        $latest_results[] = $child_results[0];
    }
}

$overall_attendance_pct = ($total_attendance_days > 0) ? round(($total_present_days / $total_attendance_days) * 100) : 0;
$total_pct = 0;
foreach ($latest_results as $res) {
    $total_pct += (float)$res['percentage'];
}
$overall_exam_avg = (!empty($latest_results)) ? round($total_pct / count($latest_results)) : 0;

// Calculate Fee Status (New)
$unpaid_count = 0;
$current_month = date('Y-m');
$current_month_name = date('M Y'); // e.g. Mar 2026
foreach ($children as $child) {
    // Check if they have previous debt, or if they haven't paid for current month
    $previous_debt = $db->getStudentPreviousDebt($child['gr_no'], $current_month);
    $assignedMonthly = $db->getStudentAssignedMonthlyFee($child);
    
    $fee_history = $db->getStudentFeeHistory($child['gr_no']);
    $current_payment = null;
    foreach ($fee_history as $h) {
        if ($h['month_for'] === $current_month) {
            $current_payment = $h;
            break;
        }
    }
    
    if ($current_payment) {
        $due_tuition = (isset($current_payment['tuition_fee']) && $current_payment['tuition_fee'] !== '') ? (float)$current_payment['tuition_fee'] : $assignedMonthly;
        $expected = $due_tuition + (float)($current_payment['admission_fee'] ?? 0) + (float)($current_payment['exam_fee'] ?? 0) + (float)($current_payment['other_fee'] ?? 0) - (float)($current_payment['discount'] ?? 0);
        $total_due_with_prev = $expected + $previous_debt;
        if ((float)$current_payment['amount_paid'] < $total_due_with_prev) {
            $unpaid_count++;
        }
    } else {
        // No payment recorded yet for the current month.
        // Total due is current month's fee + previous debt/surplus.
        $total_due_with_prev = $assignedMonthly + $previous_debt;
        if ($total_due_with_prev > 0) {
            $unpaid_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal - <?php echo htmlspecialchars($settings['school_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        primary: '#059669', // Emerald 600
                        'primary-dark': '#047857',
                    }
                }
            }
        }
    </script>
    <style>
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        
        /* Progress Bar Styles */
        .progress-circle {
            transition: stroke-dashoffset 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-900">

    <!-- Header -->
    <header class="sticky top-0 z-50 glass border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white p-2 rounded-xl shadow-sm border border-slate-100 flex items-center justify-center">
                        <img src="../assets/branding/logo.png" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-slate-800 leading-tight">Parent Portal</h1>
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest"><?php echo htmlspecialchars($settings['school_name']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-right">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Logged in as</p>
                        <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($parent_name); ?></p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button onclick="openChangePasswordModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-primary/10 hover:text-primary transition-all border border-transparent hover:border-primary/20" title="Security Settings">
                            <i class="fas fa-key"></i>
                        </button>
                        <a href="../parent_logout.php" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all font-bold text-xs uppercase tracking-widest border border-transparent shadow-sm">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        
        <!-- Welcome Section -->
        <div class="mb-12 animate-fade-in">
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-2">Assalam-o-Alaikum, <?php echo htmlspecialchars(explode(' ', $parent_name)[0]); ?>!</h2>
            <p class="text-slate-500 font-medium">You have <span class="text-primary font-bold"><?php echo count($children); ?></span> children enrolled. Track their academic performance and activities below.</p>
        </div>

        <!-- Performance Dashboard Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 animate-fade-in" style="animation-delay: 50ms">
            <!-- Academic Performance Card -->
            <div class="md:col-span-2 bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-1 w-full text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-4 mb-4">
                            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                                <i class="fas fa-award text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Academic Achievement</h3>
                        </div>
                        <p class="text-slate-500 text-sm mb-6 max-w-md mx-auto md:mx-0">Combined average score across all children in the latest examination series.</p>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Average Pct</div>
                                <div class="text-2xl font-black text-primary"><?php echo $overall_exam_avg; ?>%</div>
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Performance</div>
                                <div class="text-2xl font-black text-slate-800">
                                    <?php 
                                        if ($overall_exam_avg >= 80) echo '<span class="text-emerald-500">Excl.</span>';
                                        elseif ($overall_exam_avg >= 70) echo '<span class="text-emerald-400">V. Good</span>';
                                        elseif ($overall_exam_avg >= 50) echo '<span class="text-amber-500">Good</span>';
                                        else echo '<span class="text-slate-400">N/A</span>';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-48 h-48 relative flex-shrink-0">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent" class="text-slate-100" />
                            <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="552.92" stroke-dashoffset="<?php echo 552.92 - (552.92 * $overall_exam_avg / 100); ?>" class="text-primary progress-circle" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-black text-slate-800"><?php echo $overall_exam_avg; ?></span>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Percent</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Stats Card -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2.5rem] p-8 text-white shadow-xl relative overflow-hidden group">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                    <i class="fas fa-calendar-check text-9xl"></i>
                </div>
                
                <h3 class="text-xl font-black uppercase tracking-tight mb-8">Attendance Overview</h3>
                
                <div class="relative z-10 space-y-8">
                    <div>
                        <div class="flex justify-between items-end mb-3">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cumulative Presence</span>
                            <span class="text-3xl font-black text-primary"><?php echo $overall_attendance_pct; ?>%</span>
                        </div>
                        <div class="w-full h-3 bg-white/5 rounded-full overflow-hidden border border-white/5">
                            <div class="h-full bg-primary rounded-full transition-all duration-1000" style="width: <?php echo $overall_attendance_pct; ?>%"></div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-white/10 grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Status</div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_rgb(16,185,129)]"></div>
                                <span class="text-xs font-bold"><?php echo ($overall_attendance_pct >= 85) ? 'Excellent' : 'Average'; ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Target</div>
                            <span class="text-xs font-bold text-slate-300">Minimum 85%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Notices & Announcements -->
        <?php if (!empty($notices)): ?>
        <div class="mb-12 animate-fade-in" style="animation-delay: 100ms">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800">Notices & Announcements</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($notices as $notice): 
                    $isGeneral = ($notice['target_cnic'] === 'ALL');
                    $bgClass = $isGeneral ? 'bg-amber-50 border-amber-100' : 'bg-white border-slate-200';
                    $iconClass = $isGeneral ? 'fa-globe-americas text-amber-500' : 'fa-user-shield text-emerald-500';
                    $badgeClass = $isGeneral ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
                ?>
                <div class="p-6 rounded-3xl border <?php echo $bgClass; ?> shadow-sm hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <i class="fas <?php echo $iconClass; ?> text-sm"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest <?php echo $badgeClass; ?> px-2 py-0.5 rounded-full">
                                <?php echo $isGeneral ? 'School Announcement' : 'Private Note'; ?>
                            </span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase"><?php echo date('M d, Y', strtotime($notice['created_at'])); ?></span>
                    </div>
                    <h4 class="text-lg font-black text-slate-900 mb-2 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($notice['title']); ?></h4>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed"><?php echo nl2br(htmlspecialchars($notice['message'])); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions & Important Links (New) -->
        <div class="mb-12 animate-fade-in" style="animation-delay: 80ms">
            <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <i class="fas fa-bolt"></i>
                </div>
                Quick Portal Actions
            </h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <button onclick="openComplainModal()" class="flex flex-col items-center justify-center p-6 bg-white border-2 border-slate-100 rounded-[2.5rem] hover:border-primary hover:bg-emerald-50 transition-all group shadow-sm">
                    <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-headset"></i>
                    </div>
                    <span class="text-xs font-black text-slate-800 uppercase tracking-tight">Support & Tickets</span>
                </button>
                
                <button onclick="openIdCardModal()" class="flex flex-col items-center justify-center p-6 bg-white border-2 border-slate-100 rounded-[2.5rem] hover:border-primary hover:bg-emerald-50 transition-all group shadow-sm">
                    <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <span class="text-xs font-black text-slate-800 uppercase tracking-tight">Student E-ID</span>
                </button>

                <div class="flex flex-col items-center justify-center p-6 bg-white border-2 border-slate-100 rounded-[2.5rem] shadow-sm relative overflow-hidden">
                    <div class="w-14 h-14 <?php echo $unpaid_count > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'; ?> rounded-2xl flex items-center justify-center text-xl mb-3">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fee Status</span>
                    <span class="text-xs font-black text-center <?php echo $unpaid_count > 0 ? 'text-amber-600' : 'text-emerald-600'; ?>">
                        <?php echo $unpaid_count > 0 ? 'Pending: ' . $current_month_name : 'All Paid'; ?>
                    </span>
                </div>

                <a href="https://wa.me/<?php echo $settings['contact_number'] ?? ''; ?>" target="_blank" class="flex flex-col items-center justify-center p-6 bg-emerald-50 border-2 border-emerald-100 rounded-[2.5rem] hover:bg-emerald-100 transition-all group shadow-sm">
                    <div class="w-14 h-14 bg-emerald-500 text-white rounded-2xl flex items-center justify-center text-xl mb-3 group-hover:rotate-12 transition-transform shadow-lg shadow-emerald-200">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <span class="text-xs font-black text-emerald-700 uppercase tracking-tight">Direct Support</span>
                </a>
            </div>
        </div>

        <!-- Children Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($children as $index => $child): ?>
                <div class="group bg-white rounded-3xl shadow-sm hover:shadow-xl border border-slate-200 overflow-hidden transition-all duration-300 animate-fade-in" style="animation-delay: <?php echo $index * 100; ?>ms">
                    <!-- Child Header/Photo -->
                    <div class="h-32 bg-gradient-to-br from-emerald-500 to-teal-600 relative">
                        <div class="absolute -bottom-12 left-8 w-24 h-24 rounded-2xl border-4 border-white shadow-lg overflow-hidden bg-white">
                            <?php if (!empty($child['profile_image']) && file_exists('../' . $child['profile_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($child['profile_image']); ?>" alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 text-3xl">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-4 right-4 bg-white/20 backdrop-blur-md rounded-full px-3 py-1 text-[10px] font-bold text-white uppercase tracking-widest">
                            GR# <?php echo htmlspecialchars($child['gr_no']); ?>
                        </div>
                    </div>

                    <div class="pt-16 p-8">
                        <h3 class="text-xl font-black text-slate-900 mb-1 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($child['student_name']); ?></h3>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">Class <?php echo htmlspecialchars($child['current_class']); ?></p>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                                <p class="text-xs font-black text-emerald-600"><?php echo htmlspecialchars($child['student_status']); ?></p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Session</p>
                                <p class="text-xs font-black text-slate-700"><?php echo date('Y'); ?></p>
                            </div>
                        </div>

                        <!-- Performance Indicator (New) -->
                        <?php 
                            $c_results = $db->getStudentResults($child['id']);
                            $c_pct = 0;
                            if (!empty($c_results)) {
                                usort($c_results, function($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
                                $c_pct = $c_results[0]['percentage'];
                            }
                        ?>
                        <div class="mb-6 p-4 rounded-2xl bg-primary/5 border border-primary/10 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-primary/20 text-primary flex items-center justify-center text-xs">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <span class="text-[10px] font-black text-slate-800 uppercase tracking-tight">Recent Academic Score</span>
                            </div>
                            <span class="text-sm font-black text-primary"><?php echo $c_pct ? $c_pct.'%' : 'N/A'; ?></span>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-3">
                            <button onclick="viewChildDetail('<?php echo $child['id']; ?>', 'attendance')" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white border-2 border-slate-100 hover:border-primary hover:bg-emerald-50 transition-all group/btn">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">Attendance Records</span>
                                </div>
                                <i class="fas fa-chevron-right text-slate-300 group-hover/btn:text-primary transition-colors"></i>
                            </button>

                            <button onclick="viewChildDetail('<?php echo $child['id']; ?>', 'results')" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white border-2 border-slate-100 hover:border-primary hover:bg-emerald-50 transition-all group/btn">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">Detailed Marksheet</span>
                                </div>
                                <i class="fas fa-chevron-right text-slate-300 group-hover/btn:text-primary transition-colors"></i>
                            </button>

                            <button onclick="viewChildDetail('<?php echo $child['id']; ?>', 'certificates')" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white border-2 border-slate-100 hover:border-primary hover:bg-emerald-50 transition-all group/btn">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                                        <i class="fas fa-certificate"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">Academic Certificates</span>
                                </div>
                                <i class="fas fa-chevron-right text-slate-300 group-hover/btn:text-primary transition-colors"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Detailed View Modal (placeholder for eventually opening details) -->
    <div id="detailModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 md:p-8">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-white w-full max-w-5xl h-full max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <div id="modalChildPhoto" class="w-12 h-12 rounded-xl border border-slate-100 overflow-hidden"></div>
                    <div>
                        <h3 id="modalTitle" class="text-xl font-bold text-slate-900">Child Name</h3>
                        <p id="modalSubtitle" class="text-xs font-bold text-slate-400 uppercase tracking-widest">Detail View</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8" id="modalContent">
                <!-- Content loaded via AJAX -->
                <div class="flex flex-col items-center justify-center h-full text-slate-400">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-4"></i>
                    <p class="font-bold uppercase tracking-widest text-xs">Loading Information...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="fixed inset-0 z-[110] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closePasswordModal()"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden animate-fade-in p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mx-auto mb-4">
                    <i class="fas fa-key text-3xl"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">Change Password</h3>
                <p class="text-slate-500 text-sm font-medium">Update your portal security key</p>
            </div>
            
            <form id="changePasswordForm" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-5 py-3 bg-slate-50 border-2 border-slate-100 rounded-2xl text-slate-800 font-bold focus:border-primary outline-none transition-all" placeholder="Enter current password">
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">New Password</label>
                    <input type="password" name="new_password" required class="w-full px-5 py-3 bg-slate-50 border-2 border-slate-100 rounded-2xl text-slate-800 font-bold focus:border-primary outline-none transition-all" placeholder="Min. 6 characters">
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-5 py-3 bg-slate-50 border-2 border-slate-100 rounded-2xl text-slate-800 font-bold focus:border-primary outline-none transition-all" placeholder="Repeat new password">
                </div>
                
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-200 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs">
                    Update Security Key
                </button>
                <button type="button" onclick="closePasswordModal()" class="w-full py-3 text-slate-400 hover:text-slate-600 font-bold text-xs uppercase tracking-widest transition-colors">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- Support & Tickets Modal -->
    <div id="complainModal" class="fixed inset-0 z-[110] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeComplainModal()"></div>
        <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-fade-in flex flex-col h-[85vh]">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-white shrink-0">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Support & Tickets</h3>
                    <p class="text-slate-500 text-sm font-medium">Chat with School Administration</p>
                </div>
                <button onclick="closeComplainModal()" class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 hover:text-red-600 transition-all flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="ticketMessages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50">
                <!-- Messages will be loaded here -->
                <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-4 text-primary"></i>
                    <p class="font-bold uppercase tracking-widest text-xs">Loading conversation...</p>
                </div>
            </div>
            
            <div class="p-6 bg-white border-t border-slate-100">
                <form id="complainForm" class="flex flex-col gap-4">
                    <div class="flex gap-2 mb-2">
                        <select name="type" class="px-4 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-bold text-slate-600 outline-none focus:border-primary transition-all">
                            <option value="General">General Inquiry</option>
                            <option value="Complaint">File Complaint</option>
                            <option value="Support">Technical Support</option>
                            <option value="Fee">Fee Related</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <textarea name="message" placeholder="Type your message here..." required 
                            class="flex-1 px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-slate-800 font-bold placeholder-slate-400 outline-none focus:border-primary transition-all resize-none h-20"></textarea>
                        <button type="submit" class="w-20 bg-primary text-white rounded-2xl flex items-center justify-center hover:bg-primary-hover transition-all shadow-lg shadow-indigo-200 group">
                            <i class="fas fa-paper-plane text-xl group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ID Card Modal -->
    <div id="idCardModal" class="fixed inset-0 z-[110] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeIdCardModal()"></div>
        <div class="relative bg-white w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-fade-in flex flex-col h-[90vh]">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Student Electronic ID</h3>
                    <p class="text-slate-500 text-sm font-medium">Official Digital Identity Card</p>
                </div>
                <button onclick="closeIdCardModal()" class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 hover:text-red-600 transition-all flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-hidden bg-slate-50">
                <iframe id="idCardFrame" src="generate_id_card.php?gr_no=<?php echo urlencode($gr_nos_string); ?>&hide_controls=1" class="w-full h-full border-none"></iframe>
            </div>
            
            <div class="p-6 bg-slate-50 border-t border-slate-100 text-center">
                <button onclick="document.getElementById('idCardFrame').contentWindow.print()" class="px-8 py-3 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <i class="fas fa-print mr-2"></i> Print All Cards
                </button>
            </div>
        </div>
    </div>

    <script>
        function viewChildDetail(childId, tab) {
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('modalContent');
            const modalTitle = document.getElementById('modalTitle');
            const modalSubtitle = document.getElementById('modalSubtitle');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Reset content to loading
            content.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-4 text-primary"></i>
                    <p class="font-bold uppercase tracking-widest text-xs">Fetching Records...</p>
                </div>
            `;

            fetch(`../api/get_child_activity.php?child_id=${childId}&type=${tab}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        content.innerHTML = `<div class="p-8 text-center text-red-500 font-bold">${data.message}</div>`;
                        return;
                    }

                    modalTitle.textContent = data.child_name;
                    modalSubtitle.textContent = tab.toUpperCase();

                    if (tab === 'attendance') {
                        renderAttendance(data.data, content);
                    } else if (tab === 'results') {
                        renderResults(data.data, content, childId);
                    } else if (tab === 'certificates') {
                        renderCertificates(data.data, content);
                    } else {
                        content.innerHTML = `<div class="p-12 text-center text-slate-400">Section details coming soon.</div>`;
                    }
                })
                .catch(err => {
                    content.innerHTML = `<div class="p-8 text-center text-red-500 font-bold">Error loading data.</div>`;
                });
        }

        function renderCertificates(records, container) {
            if (!records || records.length === 0) {
                container.innerHTML = `<div class="p-20 text-center text-slate-400 font-bold">No certificates available for this student.</div>`;
                return;
            }

            let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-6">`;
            records.forEach(r => {
                html += `
                    <a href="${r.link}" target="_blank" class="flex items-center gap-6 p-6 bg-white border-2 border-slate-100 rounded-3xl hover:border-primary hover:bg-emerald-50 transition-all group/cert">
                        <div class="w-16 h-16 rounded-2xl ${r.color} flex items-center justify-center text-2xl group-hover/cert:scale-110 transition-transform">
                            <i class="${r.icon}"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-black text-slate-900 mb-1">${r.name}</h4>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Click to View & Print</p>
                        </div>
                        <i class="fas fa-external-link-alt text-slate-300 group-hover/cert:text-primary transition-colors"></i>
                    </a>
                `;
            });
            html += `</div>`;
            container.innerHTML = html;
        }

        function renderAttendance(records, container) {
            if (!records || records.length === 0) {
                container.innerHTML = `<div class="p-20 text-center text-slate-400 font-bold">No attendance records found.</div>`;
                return;
            }

            let html = `
                <div class="overflow-hidden border border-slate-100 rounded-2xl shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            <tr>
                                <th class="p-4">Date</th>
                                <th class="p-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
            `;

            records.forEach(r => {
                const statusColor = r.status === 'Present' ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50';
                html += `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 text-sm font-bold text-slate-700">${r.date}</td>
                        <td class="p-4 text-center text-xs">
                            <span class="inline-block px-3 py-1 rounded-full font-black uppercase tracking-tighter ${statusColor}">${r.status}</span>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        }

        function renderResults(records, container, childId) {
            if (!records || records.length === 0) {
                container.innerHTML = `<div class="p-20 text-center text-slate-400 font-bold">No examination results found yet.</div>`;
                return;
            }

            let html = `<div class="space-y-6">`;
            records.forEach(r => {
                html += `
                    <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <div>
                                <h4 class="text-lg font-black text-slate-900">${r.exam_type} (${r.year})</h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Marked on ${r.created_at}</p>
                            </div>
                            <div class="px-4 py-2 bg-white shadow-sm border border-slate-200 rounded-xl text-center min-w-[100px]">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Percentage</p>
                                <p class="text-lg font-black text-emerald-600">${r.percentage}%</p>
                            </div>
                            <a href="../pages/print_result.php?id=${childId}&exam_type=${encodeURIComponent(r.exam_type)}&year=${r.year}" target="_blank" class="flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                                <i class="fas fa-download"></i>
                                Download Marksheet
                            </a>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Obtained</p>
                                <p class="text-sm font-black text-slate-800">${r.total_obtained}</p>
                            </div>
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Grade</p>
                                <p class="text-sm font-black text-blue-600">${r.grade}</p>
                            </div>
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Remarks</p>
                                <p class="text-sm font-black text-slate-700">${r.remarks}</p>
                            </div>
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Max Marks</p>
                                <p class="text-sm font-black text-slate-500">${r.total_max}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
            container.innerHTML = html;
        }

        function openComplainModal() {
            document.getElementById('complainModal').classList.remove('hidden');
            document.getElementById('complainModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeComplainModal() {
            document.getElementById('complainModal').classList.add('hidden');
            document.getElementById('complainModal').classList.remove('flex');
            document.body.style.overflow = '';
            document.getElementById('complainForm').reset();
        }

        function openIdCardModal() {
            document.getElementById('idCardModal').classList.remove('hidden');
            document.getElementById('idCardModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeIdCardModal() {
            document.getElementById('idCardModal').classList.add('hidden');
            document.getElementById('idCardModal').classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.getElementById('complainForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            
            btn.textContent = 'Sending...';
            btn.disabled = true;
            btn.classList.add('opacity-70');

            const formData = new FormData(this);
            fetch('../api/send_parent_message.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    loadMessages();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
                btn.classList.remove('opacity-70');
            });
        });

        function openComplainModal() {
            document.getElementById('complainModal').classList.remove('hidden');
            document.getElementById('complainModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
            loadMessages();
        }

        function closeComplainModal() {
            document.getElementById('complainModal').classList.add('hidden');
            document.getElementById('complainModal').classList.remove('flex');
            document.body.style.overflow = '';
        }

        function loadMessages() {
            const container = document.getElementById('ticketMessages');
            fetch('../api/get_parent_messages.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        let html = '';

                        // Show resolved tickets history
                        if (data.resolved_tickets && data.resolved_tickets.length > 0) {
                            html += `<div class="mb-6">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Previous Tickets</p>`;
                            data.resolved_tickets.forEach(ticket => {
                                let cardClass = '', iconClass = '', label = '';
                                switch(ticket.status) {
                                    case 'Resolved':
                                        cardClass = 'bg-emerald-50 border-emerald-200';
                                        iconClass = 'fas fa-check-circle text-emerald-600';
                                        label = '<span class="text-xs font-black text-emerald-700 uppercase tracking-wider">Resolved</span>';
                                        break;
                                    case 'Pending':
                                        cardClass = 'bg-amber-50 border-amber-200';
                                        iconClass = 'fas fa-clock text-amber-600';
                                        label = '<span class="text-xs font-black text-amber-700 uppercase tracking-wider">Pending Review</span>';
                                        break;
                                    case 'Rejected':
                                        cardClass = 'bg-red-50 border-red-200';
                                        iconClass = 'fas fa-times-circle text-red-600';
                                        label = '<span class="text-xs font-black text-red-700 uppercase tracking-wider">Closed</span>';
                                        break;
                                    default:
                                        cardClass = 'bg-indigo-50 border-indigo-200';
                                        iconClass = 'fas fa-envelope text-indigo-600';
                                        label = '<span class="text-xs font-black text-indigo-700 uppercase tracking-wider">Admin Response</span>';
                                }
                                html += `
                                    <div class="p-4 ${cardClass} border rounded-2xl mb-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="${iconClass}"></i>
                                            ${label}
                                            <span class="ml-auto text-[8px] text-slate-400">${new Date(ticket.resolved_at).toLocaleString([], {month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit'})}</span>
                                        </div>
                                        <p class="text-sm font-medium text-slate-700 leading-relaxed">${ticket.message}</p>
                                    </div>`;
                            });
                            html += `</div>`;
                        }

                        // Show active messages
                        if (data.data.length > 0) {
                            html += `<p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Active Conversation</p>`;
                            data.data.forEach(msg => {
                                const isSentByMe = msg.sender_type === 'parent';
                                html += `
                                    <div class="flex ${isSentByMe ? 'justify-end' : 'justify-start'} w-full mb-3">
                                        <div class="max-w-[80%] ${isSentByMe ? 'bg-indigo-600 text-white rounded-l-2xl rounded-tr-2xl' : 'bg-white border border-slate-200 text-slate-800 rounded-r-2xl rounded-tl-2xl'} p-4 shadow-sm">
                                            <p class="text-sm font-medium leading-relaxed">${msg.message}</p>
                                            <div class="flex items-center justify-between mt-2 gap-4">
                                                <span class="text-[8px] font-black uppercase opacity-60">${isSentByMe ? 'You' : 'Admin'}</span>
                                                <span class="text-[8px] opacity-60">${new Date(msg.created_at).toLocaleString([], {month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit'})}</span>
                                            </div>
                                        </div>
                                    </div>`;
                            });
                        }

                        // No active ticket but has resolved → show "create new" prompt
                        if (data.data.length === 0 && data.resolved_tickets && data.resolved_tickets.length > 0) {
                            html += `
                                <div class="flex flex-col items-center justify-center py-8 text-slate-400 border-t border-slate-200 mt-4">
                                    <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mb-3">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <p class="font-bold text-xs text-slate-600 uppercase tracking-widest">No Active Ticket</p>
                                    <p class="text-[10px] mt-1 text-slate-400">Use the form below to create a new ticket</p>
                                </div>`;
                        }

                        // Completely empty state
                        if (data.data.length === 0 && (!data.resolved_tickets || data.resolved_tickets.length === 0)) {
                            html = `
                                <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                                    <i class="fas fa-comments text-4xl mb-4 opacity-20"></i>
                                    <p class="font-bold uppercase tracking-widest text-xs">No messages yet</p>
                                    <p class="text-[10px] mt-1">Start a conversation with admin</p>
                                </div>`;
                        }

                        container.innerHTML = html;
                        container.scrollTop = container.scrollHeight;
                    }
                });
        }

        function closeModal() {
            const modal = document.getElementById('detailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        function openChangePasswordModal() {
            document.getElementById('passwordModal').classList.remove('hidden');
            document.getElementById('passwordModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
            document.getElementById('passwordModal').classList.remove('flex');
            document.body.style.overflow = '';
            document.getElementById('changePasswordForm').reset();
        }

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            
            btn.textContent = 'Updating...';
            btn.disabled = true;
            btn.classList.add('opacity-70');

            const formData = new FormData(this);
            fetch('../api/change_parent_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closePasswordModal();
                }
            })
            .catch(err => {
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
                btn.classList.remove('opacity-70');
            });
        });
    </script>


</body>
</html>
