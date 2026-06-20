<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check permissions
if (!canAccessPage(basename(__FILE__))) {
    header("Location: ../index.php");
    exit;
}


$db = new Database();
$classes = $db->getClasses();
$feeStructure = $db->getFeeStructure();
$selectedMonth = isset($_GET['overview_month']) ? $_GET['overview_month'] : date('Y-m');
$feeStats = $db->getFeeStats($selectedMonth);
$defaulters = $db->getDefaulters($selectedMonth);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_structure'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $newData = [];
    foreach ($_POST['fees'] as $className => $fees) {
        $newData[$className] = [
            'monthly_fee' => (float)$fees['monthly'],
            'admission_fee' => (float)$fees['admission'],
            'exam_fee' => (float)$fees['exam']
        ];
    }

    if ($db->updateFeeStructure($newData)) {
        $success = "Fee structure updated successfully!";
        $feeStructure = $db->getFeeStructure(); // Refresh
    } else {
        $error = "Failed to update fee structure.";
    }
}

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                <i class="fas fa-money-check-alt text-indigo-600"></i>
                Fee Management
            </h1>
            <p class="text-gray-500 mt-1">Manage school fees, structures, and collections.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="switchTab('collect')" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                <i class="fas fa-plus"></i> Collect Fee
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <p class="text-green-700 font-medium"><?php echo $success; ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('overview')" id="tab-overview" class="tab-btn border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Overview
            </button>
            <button onclick="switchTab('collect')" id="tab-collect" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Collect Fee
            </button>
            <button onclick="switchTab('structure')" id="tab-structure" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Fee Structure
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Collection History
            </button>
            <button onclick="switchTab('defaulters')" id="tab-defaulters" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                Defaulters List
                <span id="defaulter_badge" class="bg-red-50 text-red-600 px-2 py-0.5 rounded-full text-[10px] font-black ring-1 ring-red-100 shadow-sm">
                    <?php echo count($defaulters); ?>
                </span>
            </button>
        </nav>
    </div>

    <!-- Tab Contents -->
    <div id="content-overview" class="tab-content">
        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Fee Overview</h2>
                <p class="text-xs text-gray-500">Viewing statistics for <?php echo date('F Y', strtotime($selectedMonth)); ?></p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                <!-- Student Search -->
                <div class="relative flex-1 sm:min-w-[280px] lg:min-w-[320px]">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400 text-sm"></i>
                    <input type="text" id="overview_student_search" autocomplete="off"
                           placeholder="Search student (Name or GR No)..."
                           class="w-full border-2 border-indigo-100 rounded-lg pl-10 pr-4 py-2.5 focus:border-indigo-500 outline-none font-medium text-gray-700 bg-indigo-50/30 transition-all hover:border-indigo-200 placeholder:text-gray-400">
                    <div id="overview_search_results" class="absolute z-40 w-full bg-white mt-1 border border-gray-200 rounded-xl shadow-2xl hidden max-h-72 overflow-y-auto custom-scrollbar"></div>
                </div>
                <form method="GET" class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-initial">
                        <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400 text-xs"></i>
                        <input type="month" name="overview_month" value="<?php echo $selectedMonth; ?>" onchange="this.form.submit()" class="w-full sm:w-auto border-2 border-gray-100 rounded-lg pl-9 pr-4 py-2 focus:border-indigo-500 outline-none font-bold text-gray-700 bg-gray-50/50 transition-all hover:border-indigo-200">
                    </div>
                    <noscript><button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm">Apply</button></noscript>
                </form>
            </div>
        </div>

        <!-- Selected Student Fee Profile Panel -->
        <div id="overview_student_panel" class="hidden mb-8 animate-fade-in">
            <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-indigo-50/80 to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div id="overview_student_header" class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white text-lg font-black shadow-md" id="overview_student_avatar">?</div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900" id="overview_student_name">—</h3>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 font-bold mt-0.5">
                                <span id="overview_student_gr"><i class="fas fa-id-card text-indigo-400 mr-1"></i> GR: —</span>
                                <span id="overview_student_class"><i class="fas fa-school text-indigo-400 mr-1"></i> —</span>
                                <span id="overview_student_fee"><i class="fas fa-tag text-indigo-400 mr-1"></i> Monthly: Rs. —</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
                        <button onclick="showAddArrearsModal()" class="p-2.5 bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-600 hover:text-white transition shadow-sm flex items-center gap-2 text-xs font-bold" title="Add previous dues / arrears">
                            <i class="fas fa-plus-circle"></i> <span class="hidden sm:inline">Add Arrears</span>
                        </button>
                        <button onclick="viewOverviewStudentProfile()" class="p-2.5 bg-teal-50 text-teal-600 rounded-lg hover:bg-teal-600 hover:text-white transition shadow-sm flex items-center gap-2 text-xs font-bold" title="View Complete Profile">
                            <i class="fas fa-eye"></i> <span class="hidden sm:inline">View Profile</span>
                        </button>
                        <button onclick="editOverviewStudentFees()" class="p-2.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm flex items-center gap-2 text-xs font-bold" title="Add / Edit Fees">
                            <i class="fas fa-edit"></i> <span class="hidden sm:inline">Add / Edit Fee</span>
                        </button>
                        <button onclick="clearOverviewStudent()" class="p-2.5 bg-gray-50 text-gray-400 rounded-lg hover:bg-gray-200 hover:text-gray-600 transition" title="Clear">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="overview_student_summary" class="px-5 py-3 bg-gray-50/50 border-b border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                    <!-- Filled via JS -->
                </div>
                <div id="overview_student_history" class="overflow-x-auto">
                    <div class="px-6 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading fee history...</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                    </div>
                </div>
                <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Collections (<?php echo date('M Y', strtotime($selectedMonth)); ?>)</h3>
                <p class="text-2xl font-bold text-gray-900 mt-1">Rs. <?php echo number_format($feeStats['this_month'], 2); ?></p>
                <p class="text-xs text-green-600 mt-2"><i class="fas fa-check-circle"></i> For selected period</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                        <i class="fas fa-hand-holding-usd fa-lg"></i>
                    </div>
                </div>
                <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Today's Collections</h3>
                <p class="text-2xl font-bold text-gray-900 mt-1">Rs. <?php echo number_format($feeStats['today'], 2); ?></p>
                <p class="text-xs text-indigo-600 mt-2">Recorded today</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-50 rounded-lg text-amber-600">
                        <i class="fas fa-user-clock fa-lg"></i>
                    </div>
                </div>
                <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Defaulters (<?php echo date('M Y', strtotime($selectedMonth)); ?>)</h3>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($defaulters); ?></p>
                <p class="text-xs text-amber-600 mt-2">Pending for <?php echo date('F Y', strtotime($selectedMonth)); ?></p>
            </div>
        </div>
        
        <!-- Class-wise Breakdown -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 mt-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-indigo-500"></i> Collections by Class (<?php echo date('F Y', strtotime($selectedMonth)); ?>)
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($feeStats['class_breakdown'] as $cls => $amt): ?>
                <div onclick="showClassDetail('<?php echo htmlspecialchars($cls); ?>')" class="bg-gray-50 rounded-lg p-3 border border-gray-100 hover:border-indigo-300 hover:shadow-md transition cursor-pointer group">
                    <div class="flex justify-between items-start mb-1">
                        <div class="text-[10px] text-gray-500 font-bold uppercase"><?php echo htmlspecialchars($cls); ?></div>
                        <i class="fas fa-external-link-alt text-[8px] text-gray-300 group-hover:text-indigo-400"></i>
                    </div>
                    <div class="text-sm font-bold text-gray-900">Rs. <?php echo number_format($amt); ?></div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($feeStats['class_breakdown'])): ?>
                <div class="col-span-full py-4 text-center text-gray-400 text-sm italic">No collections recorded yet for this month.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Recent Collections</h3>
                <a href="#" onclick="switchTab('history')" class="text-indigo-600 text-sm font-medium hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Month</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Method</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($feeStats['recent'])): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">No recent collections found.</td>
                            </tr>
                        <?php else: 
                            // Get student names for recent list
                            $allStudents = $db->readData();
                            $sMap = [];
                            foreach ($allStudents as $st) $sMap[$st['gr_no']] = $st['student_name'];
                        ?>
                            <?php foreach ($feeStats['recent'] as $r): ?>
                            <tr class="hover:bg-indigo-50/50 cursor-pointer group/row transition-colors" onclick="selectStudentWithMonth('<?php echo $r['gr_no']; ?>', '<?php echo addslashes($sMap[$r['gr_no']] ?? 'Unknown'); ?>', '<?php echo $r['month_for']; ?>')">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800 group-hover/row:text-indigo-600 transition-colors"><?php echo htmlspecialchars($sMap[$r['gr_no']] ?? 'Unknown'); ?></div>
                                    <div class="text-[10px] text-gray-500">GR: <?php echo $r['gr_no']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md text-xs"><?php echo $r['month_for']; ?></span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-700">Rs. <?php echo number_format($r['amount_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-medium"><?php echo date('d M, Y', strtotime($r['payment_date'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-white border border-gray-200 rounded-full text-[10px] uppercase font-black text-gray-500 shadow-sm transition-all group-hover/row:border-indigo-200 group-hover/row:text-indigo-600">
                                        <?php echo $r['payment_method']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="content-collect" class="tab-content hidden">
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Class selection -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-layer-group text-indigo-500"></i> Class View
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-1.5">1. School Stage</label>
                            <select id="stage_selector" onchange="filterClassesByStage()" class="w-full border-2 border-gray-100 rounded-xl p-3 focus:ring-indigo-500 focus:border-indigo-500 font-bold text-gray-700 bg-gray-50/50">
                                <option value="">-- All Stages --</option>
                                <option value="Pre-Primary">Pre-Primary</option>
                                <option value="Elementary">Elementary</option>
                                <option value="College">College</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-1.5">2. Select Class</label>
                            <select id="class_selector" onchange="loadClassStudents()" class="w-full border-2 border-gray-100 rounded-xl p-3 focus:ring-indigo-500 focus:border-indigo-500 font-bold text-gray-700 bg-gray-50/50">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['class_name']; ?>" data-stage="<?php echo $c['stage'] ?? 'Elementary'; ?>"><?php echo $c['class_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="class_students_list" class="mt-6 space-y-2 max-h-[500px] overflow-y-auto custom-scrollbar">
                        <p class="text-xs text-gray-400 text-center py-8">Select a class to see student list.</p>
                    </div>
                </div>
            </div>

            <!-- Right: Search & Form -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Fee Collection Form</h3>
                    <div class="space-y-6">
                        <div id="manual_search_container">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Student (Name or GR No)</label>
                            <div class="relative z-20">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="student_search" class="pl-10 block w-full border border-gray-300 rounded-lg py-3 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Type to search...">
                                <div id="search_results" class="absolute z-30 w-full bg-white mt-1 border border-gray-200 rounded-lg shadow-xl hidden max-h-60 overflow-y-auto"></div>
                            </div>
                        </div>

                        <div id="collection_details" class="hidden animate-fade-in">
                            <div class="p-12 text-center text-gray-400">
                                <i class="fas fa-user-check text-4xl mb-3 opacity-20"></i>
                                <p>Select a student to record fee collection.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="content-structure" class="tab-content hidden">
        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <?php echo csrfInput(); ?>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Class-wise Fee Structure</h3>
                    <p class="text-sm text-gray-500">Define default fees for each class.</p>
                </div>
                <button type="submit" name="update_structure" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 transition shadow-md">
                    <i class="fas fa-save mr-2"></i> Save Structure
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-4">Class Name</th>
                            <th class="px-6 py-4">Monthly Fee (Rs.)</th>
                            <th class="px-6 py-4">Admission Fee (Rs.)</th>
                            <th class="px-6 py-4">Exam Fee (Rs.)</th>
                            <th class="px-6 py-4">Last Updated</th>
                        </tr>
                    </thead>
                    <?php 
                    $groupedClasses = [];
                    foreach (['Pre-Primary', 'Elementary', 'College'] as $s) $groupedClasses[$s] = [];
                    foreach ($classes as $c) {
                        $s = $c['stage'] ?? 'Elementary';
                        $groupedClasses[$s][] = $c;
                    }
                    ?>
                    
                    <?php foreach ($groupedClasses as $stageName => $stageClasses): if (empty($stageClasses)) continue; ?>
                    <thead class="bg-indigo-50 text-indigo-600 text-xs uppercase font-black tracking-widest">
                        <tr>
                            <th colspan="5" class="px-6 py-3 border-y border-indigo-100">
                                <i class="fas fa-layer-group mr-2"></i> <?php echo $stageName; ?> Section
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 mb-6">
                        <?php foreach ($stageClasses as $class): 
                            $name = $class['class_name'];
                            $fees = $feeStructure[$name] ?? ['monthly_fee' => 0, 'admission_fee' => 0, 'exam_fee' => 0, 'updated_at' => '-'];
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-700"><?php echo htmlspecialchars($name); ?></td>
                            <td class="px-6 py-4">
                                <input type="number" name="fees[<?php echo $name; ?>][monthly]" value="<?php echo $fees['monthly_fee']; ?>" class="w-32 border-2 border-gray-100 rounded-lg px-3 py-1.5 focus:border-indigo-500 outline-none font-bold">
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="fees[<?php echo $name; ?>][admission]" value="<?php echo $fees['admission_fee']; ?>" class="w-32 border-2 border-gray-100 rounded-lg px-3 py-1.5 focus:border-indigo-500 outline-none font-bold">
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="fees[<?php echo $name; ?>][exam]" value="<?php echo $fees['exam_fee']; ?>" class="w-32 border-2 border-gray-100 rounded-lg px-3 py-1.5 focus:border-indigo-500 outline-none font-bold">
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-400 font-mono"><?php echo $fees['updated_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endforeach; ?>
                </table>
            </div>
        </form>
    </div>

    <div id="content-history" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Full History Table -->
            <div class="p-6 border-b border-gray-100 flex flex-wrap gap-4 justify-between items-center">
                <div class="flex items-center gap-3">
                    <h3 class="font-bold text-gray-800">All Collections</h3>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
                        <input type="text" id="history_search" placeholder="Search name or GR..."
                               class="text-xs border border-gray-200 rounded-lg pl-8 pr-3 py-2 w-48 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm"
                               oninput="loadHistory()">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select id="history_stage" class="text-sm border rounded px-3 py-1 bg-white focus:ring-2 focus:ring-indigo-500 transition" onchange="filterHistoryClasses()">
                        <option value="">All Stages</option>
                        <option value="Pre-Primary">Pre-Primary</option>
                        <option value="Elementary">Elementary</option>
                        <option value="College">College</option>
                    </select>
                    <select id="history_class" class="text-sm border rounded px-3 py-1 bg-white focus:ring-2 focus:ring-indigo-500 transition" onchange="loadHistory()">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class_name']; ?>" data-stage="<?php echo $c['stage'] ?? 'Elementary'; ?>"><?php echo $c['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="relative flex items-center">
                        <input type="month" id="history_month" class="text-sm border rounded-l px-3 py-1 focus:ring-2 focus:ring-indigo-500 transition" onchange="loadHistory()">
                        <button onclick="document.getElementById('history_month').value=''; loadHistory();" class="text-[10px] bg-gray-100 border border-l-0 rounded-r px-2 py-1.5 hover:bg-gray-200 transition font-bold text-gray-600" title="All Time History">
                            ALL TIME
                        </button>
                    </div>
                    <button type="button" id="history_sort_unpaid_btn" onclick="toggleHistorySortUnpaid()"
                            class="text-xs border border-red-200 bg-red-50 text-red-600 rounded-lg px-3 py-1.5 font-bold hover:bg-red-100 transition flex items-center gap-1.5 shadow-sm"
                            title="Show unpaid students on top">
                        <i class="fas fa-sort-amount-down-alt text-[10px]"></i> Unpaid
                    </button>
                    <div class="relative" id="history_download_wrap">
                        <button type="button" onclick="toggleHistoryDownloadMenu()"
                                class="text-xs border border-indigo-200 bg-indigo-50 text-indigo-700 rounded-lg px-3 py-1.5 font-bold hover:bg-indigo-100 transition flex items-center gap-1.5 shadow-sm">
                            <i class="fas fa-download text-[10px]"></i> Download <i class="fas fa-chevron-down text-[8px]"></i>
                        </button>
                        <div id="history_download_menu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden">
                            <button type="button" onclick="downloadFeeHistory('pdf')" class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-indigo-50 flex items-center gap-2 border-b border-gray-100">
                                <i class="fas fa-file-pdf text-red-500"></i> PDF
                            </button>
                            <button type="button" onclick="downloadFeeHistory('excel')" class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-indigo-50 flex items-center gap-2">
                                <i class="fas fa-file-excel text-green-600"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>

            </div>
            <div id="history_table_container">
                <!-- Loaded via AJAX -->
                <div class="px-6 py-12 text-center text-gray-400">Loading history...</div>
            </div>
        </div>
    </div>

    <div id="content-defaulters" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex flex-wrap gap-4 justify-between items-center bg-gray-50/30">
                <div class="flex items-center gap-3">
                    <h3 class="font-bold text-gray-800">Defaulters List</h3>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
                        <input type="text" id="defaulter_search" placeholder="Search name or GR..." 
                               class="text-xs border border-gray-200 rounded-lg pl-8 pr-3 py-2 w-48 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm"
                               oninput="loadDefaulters()">
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <select id="defaulter_stage" class="text-xs border border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm outline-none focus:ring-2 focus:ring-indigo-500" onchange="filterDefaulterClasses(); loadDefaulters();">
                        <option value="">All Stages</option>
                        <option value="Pre-Primary">Pre-Primary</option>
                        <option value="Elementary">Elementary</option>
                        <option value="College">College</option>
                    </select>
                    <select id="defaulter_class" class="text-xs border border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm outline-none focus:ring-2 focus:ring-indigo-500" onchange="loadDefaulters()">
                        <option value="">All Classes</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['class_name']); ?>" data-stage="<?php echo $c['stage']; ?>">
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="month" id="defaulter_month" class="text-xs border border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo date('Y-m'); ?>" onchange="loadDefaulters()">
                </div>
            </div>
            <div id="defaulters_table_container">
                <div class="px-6 py-12 text-center text-gray-400">Loading defaulters...</div>
            </div>
        </div>
    </div>
</div>

<!-- Class Detail Modal -->
<div id="class_detail_modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="window.closeFeeModal()">

            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="fee_modal_dialog" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
            <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-indigo-50/30">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" id="modal_title">Class Details</h3>
                    <p class="text-xs text-indigo-600 font-medium" id="modal_subtitle">Payment status for March 2026</p>
                </div>
                <button onclick="window.closeFeeModal()" class="text-gray-400 hover:text-gray-600 transition p-1">

                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto custom-scrollbar" id="modal_content">
                <!-- Content loaded via JS -->
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-100">
                <button onclick="window.closeFeeModal()" class="bg-white text-gray-700 font-bold py-2 px-6 rounded-lg border border-gray-300 hover:bg-gray-100 transition text-sm">

                    Close
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// Global filter context
const OVERVIEW_MONTH = '<?php echo $selectedMonth; ?>';
let overviewSelectedStudent = null;

// ── Overview Student Search ──
const overviewSearch = document.getElementById('overview_student_search');
const overviewSearchResults = document.getElementById('overview_search_results');
let overviewSearchTimeout = null;

if (overviewSearch) {
    overviewSearch.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            overviewSearchResults.classList.add('hidden');
            return;
        }

        clearTimeout(overviewSearchTimeout);
        overviewSearchTimeout = setTimeout(() => {
            fetch(`../api/get_students.php?search=${encodeURIComponent(query)}&json=1&include_alumni=1&autocomplete=1`)
                .then(res => res.json())
                .then(data => {
                    const students = data.students || [];
                    if (students.length === 0) {
                        overviewSearchResults.innerHTML = '<div class="px-4 py-3 text-gray-500 italic text-sm">No students found.</div>';
                    } else {
                        overviewSearchResults.innerHTML = '';
                        students.forEach(s => {
                            const displayClass = s.student_status === 'Alumni'
                                ? `Alumni (${s.last_class || s.current_class})`
                                : s.current_class;
                            const div = document.createElement('div');
                            div.className = 'px-4 py-3 hover:bg-indigo-50 border-b border-gray-100 last:border-0 flex items-center justify-between gap-3 group';

                            const info = document.createElement('div');
                            info.className = 'flex-1 min-w-0 cursor-pointer';
                            info.innerHTML = `
                                <div class="font-bold text-gray-800 truncate">${s.student_name}</div>
                                <div class="text-[10px] text-gray-500 uppercase font-medium">GR: ${s.gr_no} | ${displayClass}</div>
                            `;
                            info.onclick = () => selectOverviewStudent(s);

                            const actions = document.createElement('div');
                            actions.className = 'flex items-center gap-1 flex-shrink-0';

                            const eyeBtn = document.createElement('button');
                            eyeBtn.className = 'p-1.5 bg-teal-50 text-teal-600 rounded-lg hover:bg-teal-600 hover:text-white transition text-xs';
                            eyeBtn.title = 'View Profile';
                            eyeBtn.innerHTML = '<i class="fas fa-eye"></i>';
                            eyeBtn.onclick = (e) => { e.stopPropagation(); selectOverviewStudent(s); viewOverviewStudentProfile(); };

                            const editBtn = document.createElement('button');
                            editBtn.className = 'p-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition text-xs';
                            editBtn.title = 'Add / Edit Fee';
                            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                            editBtn.onclick = (e) => { e.stopPropagation(); selectOverviewStudent(s); editOverviewStudentFees(); };

                            actions.appendChild(eyeBtn);
                            actions.appendChild(editBtn);
                            div.appendChild(info);
                            div.appendChild(actions);
                            overviewSearchResults.appendChild(div);
                        });
                    }
                    overviewSearchResults.classList.remove('hidden');
                });
        }, 200); // 200ms debounce
    });

    document.addEventListener('click', function(e) {
        if (!overviewSearch.contains(e.target) && !overviewSearchResults.contains(e.target)) {
            overviewSearchResults.classList.add('hidden');
        }
    });
}

window.selectOverviewStudent = function(student) {
    overviewSelectedStudent = student;
    overviewSearch.value = student.student_name;
    overviewSearchResults.classList.add('hidden');

    const panel = document.getElementById('overview_student_panel');
    panel.classList.remove('hidden');

    document.getElementById('overview_student_name').innerText = student.student_name;
    document.getElementById('overview_student_gr').innerHTML = `<i class="fas fa-id-card text-indigo-400 mr-1"></i> GR: ${student.gr_no}`;
    document.getElementById('overview_student_class').innerHTML = `<i class="fas fa-school text-indigo-400 mr-1"></i> ${student.current_class}`;
    document.getElementById('overview_student_avatar').innerText = student.student_name.charAt(0).toUpperCase();

    const historyContainer = document.getElementById('overview_student_history');
    historyContainer.innerHTML = '<div class="px-6 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading fee history...</div>';

    const summaryContainer = document.getElementById('overview_student_summary');
    summaryContainer.innerHTML = '<div class="col-span-full py-2 text-center text-gray-400 text-xs"><i class="fas fa-spinner fa-spin"></i></div>';

    fetch(`../api/get_fee_status.php?gr_no=${student.gr_no}&month=${OVERVIEW_MONTH}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) return;
            const monthlyFee = data.structure.monthly_fee || 0;
            document.getElementById('overview_student_fee').innerHTML = `<i class="fas fa-tag text-indigo-400 mr-1"></i> Monthly: Rs. ${Number(monthlyFee).toLocaleString()}`;

            const existing = data.existing_payment;
            const prevDebt = data.previous_debt || 0;
            let monthStatus = 'Unpaid', statusColor = 'text-red-600', statusBg = 'bg-red-50';
            let monthPaid = 0;
            if (existing) {
                monthPaid = parseFloat(existing.amount_paid) || 0;
                const due = (parseFloat(existing.tuition_fee) || monthlyFee) + (parseFloat(existing.admission_fee)||0) + (parseFloat(existing.exam_fee)||0) + (parseFloat(existing.other_fee)||0) - (parseFloat(existing.discount)||0);
                const debt = Math.max(0, due - monthPaid);
                if (debt > 0) { monthStatus = 'Partial'; statusColor = 'text-amber-600'; statusBg = 'bg-amber-50'; }
                else { monthStatus = 'Paid'; statusColor = 'text-green-600'; statusBg = 'bg-green-50'; }
            }

            summaryContainer.innerHTML = `
                <div class="p-3 rounded-lg ${statusBg}">
                    <p class="text-[9px] font-black uppercase text-gray-400 tracking-wider">${new Date(OVERVIEW_MONTH + '-01').toLocaleString('en-US', {month:'short', year:'numeric'})} Status</p>
                    <p class="text-sm font-black ${statusColor}">${monthStatus}</p>
                </div>
                <div class="p-3 rounded-lg bg-indigo-50">
                    <p class="text-[9px] font-black uppercase text-gray-400 tracking-wider">This Month Paid</p>
                    <p class="text-sm font-black text-indigo-700">Rs. ${monthPaid.toLocaleString()}</p>
                </div>
                <div class="p-3 rounded-lg bg-amber-50">
                    <p class="text-[9px] font-black uppercase text-gray-400 tracking-wider">Previous Arrears</p>
                    <p class="text-sm font-black text-amber-700">Rs. ${Number(prevDebt).toLocaleString()}</p>
                </div>
                <div class="p-3 rounded-lg bg-slate-50">
                    <p class="text-[9px] font-black uppercase text-gray-400 tracking-wider">Assigned Fee</p>
                    <p class="text-sm font-black text-slate-700">Rs. ${Number(monthlyFee).toLocaleString()}</p>
                </div>
            `;
        });

    fetch(`../api/get_fee_history.php?gr_no=${student.gr_no}`)
        .then(res => res.text())
        .then(html => {
            historyContainer.innerHTML = html;
        });
};

window.viewOverviewStudentProfile = function() {
    if (!overviewSelectedStudent) return;
    showStudentFeeProfile(overviewSelectedStudent.gr_no, overviewSelectedStudent.student_name);
};

window.showAddArrearsModal = function() {
    if (!overviewSelectedStudent) return;

    const modal = document.getElementById('class_detail_modal');
    const content = document.getElementById('modal_content');
    const title = document.getElementById('modal_title');
    const subtitle = document.getElementById('modal_subtitle');
    const dialog = document.getElementById('fee_modal_dialog');
    const student = overviewSelectedStudent;
    const defaultMonth = new Date();
    defaultMonth.setMonth(defaultMonth.getMonth() - 1);
    const defaultMonthVal = defaultMonth.toISOString().slice(0, 7);

    title.innerText = 'Add Arrears / Dues';
    subtitle.innerText = `${student.student_name} — GR: ${student.gr_no}`;
    content.innerHTML = `
        <form id="add_arrears_form" class="space-y-5">
            <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                <i class="fas fa-info-circle mr-1"></i>
                Add previous month dues for this student. Amount will be added to the selected month's balance and shown in remarks.
            </div>
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">For Month</label>
                <input type="month" name="month_for" value="${defaultMonthVal}" required
                       class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 font-bold text-gray-800 focus:border-amber-500 outline-none">
            </div>
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Arrears Amount (Rs.)</label>
                <input type="number" name="amount" min="1" step="1" required placeholder="e.g. 500"
                       class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 font-bold text-amber-700 focus:border-amber-500 outline-none text-lg">
            </div>
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Remarks <span class="text-red-500">*</span></label>
                <textarea name="remarks" rows="3" required placeholder="e.g. March & April fee pending, old session balance..."
                          class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-gray-700 focus:border-amber-500 outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="w-full bg-amber-600 text-white font-black py-4 rounded-xl hover:bg-amber-700 transition shadow-lg flex items-center justify-center gap-2 uppercase tracking-widest text-sm">
                <i class="fas fa-plus-circle"></i> Add Arrears
            </button>
        </form>
    `;

    if (dialog) {
        dialog.classList.remove('sm:max-w-2xl', 'sm:max-w-4xl');
        dialog.classList.add('sm:max-w-lg');
    }
    if (modal) {
        modal.style.display = 'block';
        modal.classList.remove('hidden');
    }

    document.getElementById('add_arrears_form').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('gr_no', student.gr_no);

        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch('../api/add_fee_arrears.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeFeeModal();
                    selectOverviewStudent(student);
                    alert('Arrears added successfully!');
                } else {
                    alert(data.error || 'Failed to add arrears');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-plus-circle"></i> Add Arrears';
                }
            })
            .catch(() => {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus-circle"></i> Add Arrears';
            });
    };
};

window.openEditForStudent = function(gr, name, month) {
    overviewSelectedStudent = { gr_no: gr, student_name: name };
    editOverviewStudentFees(month);
};

window.editOverviewStudentFees = function(month) {
    if (!overviewSelectedStudent) return;
    switchTab('collect');
    if (month) {
        selectStudentWithMonth(overviewSelectedStudent.gr_no, overviewSelectedStudent.student_name, month);
    } else {
        selectStudent(overviewSelectedStudent);
    }
};

window.clearOverviewStudent = function() {
    overviewSelectedStudent = null;
    overviewSearch.value = '';
    document.getElementById('overview_student_panel').classList.add('hidden');
};

window.showStudentFeeProfile = function(gr, name) {
    const modal = document.getElementById('class_detail_modal');
    const content = document.getElementById('modal_content');
    const title = document.getElementById('modal_title');
    const subtitle = document.getElementById('modal_subtitle');

    title.innerText = name;
    subtitle.innerText = `Complete Fee Profile — GR: ${gr}`;
    content.innerHTML = '<div class="py-12 text-center"><i class="fas fa-circle-notch fa-spin fa-2x text-indigo-500"></i><p class="mt-2 text-gray-400">Loading profile...</p></div>';

    const dialog = document.getElementById('fee_modal_dialog');
    if (dialog) {
        dialog.classList.remove('sm:max-w-2xl');
        dialog.classList.add('sm:max-w-4xl');
    }
    if (modal) {
        modal.style.display = 'block';
        modal.classList.remove('hidden');
    }

    Promise.all([
        fetch(`../api/get_fee_status.php?gr_no=${gr}&month=${OVERVIEW_MONTH}`).then(r => r.json()),
        fetch(`../api/get_fee_history.php?gr_no=${gr}`).then(r => r.text())
    ]).then(([statusData, historyHtml]) => {
        const s = statusData.student || {};
        overviewSelectedStudent = { gr_no: gr, student_name: name, current_class: s.current_class || '' };
        const struct = statusData.structure || {};
        const prevDebt = statusData.previous_debt || 0;
        const existing = statusData.existing_payment;

        let monthBadge = '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">UNPAID</span>';
        if (existing) {
            const paid = parseFloat(existing.amount_paid) || 0;
            const due = (parseFloat(existing.tuition_fee)||struct.monthly_fee) + (parseFloat(existing.admission_fee)||0) + (parseFloat(existing.exam_fee)||0) + (parseFloat(existing.other_fee)||0) - (parseFloat(existing.discount)||0);
            monthBadge = paid >= due
                ? '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold">PAID</span>'
                : '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">PARTIAL</span>';
        }

        content.innerHTML = `
            <div class="mb-6 p-4 bg-indigo-50/50 rounded-xl border border-indigo-100">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase text-indigo-400 tracking-wider">Student Info</p>
                        <p class="font-bold text-gray-800">${s.current_class || '—'}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black uppercase text-indigo-400 tracking-wider">${new Date(OVERVIEW_MONTH+'-01').toLocaleString('en-US',{month:'long',year:'numeric'})}</p>
                        ${monthBadge}
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Monthly Fee</p>
                        <p class="font-black text-gray-800">Rs. ${Number(struct.monthly_fee||0).toLocaleString()}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Admission</p>
                        <p class="font-black text-gray-800">Rs. ${Number(struct.admission_fee||0).toLocaleString()}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Exam Fee</p>
                        <p class="font-black text-gray-800">Rs. ${Number(struct.exam_fee||0).toLocaleString()}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Arrears</p>
                        <p class="font-black text-amber-700">Rs. ${Number(prevDebt).toLocaleString()}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-4">
                    <button onclick="closeFeeModal(); showAddArrearsModal();" class="flex-1 min-w-[120px] bg-amber-600 text-white text-xs font-bold py-2.5 rounded-lg hover:bg-amber-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-plus-circle"></i> Add Arrears
                    </button>
                    <button onclick="closeFeeModal(); openEditForStudent('${gr}', ${JSON.stringify(name)});" class="flex-1 min-w-[120px] bg-indigo-600 text-white text-xs font-bold py-2.5 rounded-lg hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-edit"></i> Add / Edit Fee
                    </button>
                    <a href="print_fee_history.php?gr_no=${gr}" target="_blank" class="flex-1 min-w-[120px] bg-gray-100 text-gray-700 text-xs font-bold py-2.5 rounded-lg hover:bg-gray-200 transition flex items-center justify-center gap-2">
                        <i class="fas fa-print"></i> Print History
                    </a>
                </div>
            </div>
            <h4 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-3 flex items-center gap-2"><i class="fas fa-history text-indigo-400"></i> Payment History</h4>
            ${historyHtml}
        `;
    });
};

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById('content-' + tabId).classList.remove('hidden');
    
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('border-indigo-500', 'text-indigo-600');
        b.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById('tab-' + tabId).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabId).classList.add('border-indigo-500', 'text-indigo-600');
}

// Student Search logic
const studentSearch = document.getElementById('student_search');
const searchResults = document.getElementById('search_results');
let searchTimeout = null;

if (studentSearch) {
    studentSearch.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetch(`../api/get_students.php?search=${encodeURIComponent(query)}&json=1&include_alumni=1&autocomplete=1`)
                .then(res => res.json())
                .then(data => {
                    const students = data.students;
                    if (students && students.length > 0) {
                        searchResults.innerHTML = '';
                        students.forEach(s => {
                            const displayClass = s.student_status === 'Alumni'
                                ? `Alumni (${s.last_class || s.current_class})`
                                : s.current_class;
                            const div = document.createElement('div');
                            div.className = 'px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0';
                            div.innerHTML = `
                                <div class="font-bold text-gray-800">${s.student_name}</div>
                                <div class="text-xs text-gray-500 uppercase">GR: ${s.gr_no} | Class: ${displayClass}</div>
                            `;
                            div.onclick = () => selectStudent(s);
                            searchResults.appendChild(div);
                        });
                        searchResults.classList.remove('hidden');
                    } else {
                        searchResults.innerHTML = '<div class="px-4 py-3 text-gray-500 italic">No students found.</div>';
                        searchResults.classList.remove('hidden');
                    }
                });
        }, 200); // 200ms debounce
    });
}

function selectStudent(student) {
    searchResults.classList.add('hidden');
    studentSearch.value = student.student_name;
    
    const container = document.getElementById('collection_details');
    container.innerHTML = `<div class="p-12 text-center"><i class="fas fa-spinner fa-spin fa-2x text-indigo-500"></i></div>`;
    container.classList.remove('hidden');

    const currentMonth = new Date().toISOString().slice(0, 7);
    fetch(`../api/get_fee_status.php?gr_no=${student.gr_no}&month=${currentMonth}`)
        .then(res => res.json())
        .then(data => {
            window.currentStudentFeeData = data;
            renderCollectionForm(student, data);
        });
}

window.selectStudentWithMonth = function(gr_no, name, month) {
    switchTab('collect');
    const student = { gr_no: gr_no, student_name: name };
    
    const container = document.getElementById('collection_details');
    container.innerHTML = `<div class="p-12 text-center"><i class="fas fa-spinner fa-spin fa-2x text-indigo-500"></i></div>`;
    container.classList.remove('hidden');

    fetch(`../api/get_fee_status.php?gr_no=${gr_no}&month=${month}`)
        .then(res => res.json())
        .then(data => {
            window.currentStudentFeeData = data;
            renderCollectionForm(student, data);
            // Ensure student name is cleared from search if picking from recent
            if(studentSearch) studentSearch.value = name;
        });
}

window.checkFeeStatusForMonth = function(gr_no) {
    const month = document.getElementById('fee_month_picker').value;
    if (!month) return;
    
    // Extract student name from the UI
    const nameEl = document.querySelector('#collection_details h2');
    const name = nameEl ? nameEl.innerText : 'Student';
    
    fetch(`../api/get_fee_status.php?gr_no=${gr_no}&month=${month}`)
        .then(res => res.json())
        .then(data => {
            window.currentStudentFeeData = data;
            renderCollectionForm({ gr_no: gr_no, student_name: name }, data);
        });
}

function renderCollectionForm(student, feeData) {
    const container = document.getElementById('collection_details');
    const existing = feeData.existing_payment;
    const isUpdate = !!existing;
    
    // Set base fee for recalculation
    currentBaseFee = parseInt(feeData.structure.monthly_fee) || 0;
    window.currentStudentFeeData = feeData;

    container.innerHTML = `
        <div class="bg-indigo-50 rounded-xl p-6 mb-8 flex flex-col md:flex-row items-center justify-between border-2 border-indigo-100 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-100/50 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="relative z-10 w-full md:w-auto mb-4 md:mb-0">
                <span class="px-3 py-1 bg-indigo-600/10 text-indigo-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-full mb-3 inline-block">Student Profile</span>
                <p class="text-2xl font-black text-gray-900 leading-none mb-2">${student.student_name}</p>
                <div class="flex items-center gap-3 text-sm text-gray-500 font-bold">
                    <span class="flex items-center gap-1"><i class="fas fa-id-card text-indigo-400"></i> ${student.gr_no}</span>
                    <span class="flex items-center gap-1"><i class="fas fa-school text-indigo-400"></i> ${student.current_class}</span>
                </div>
            </div>
            
            <div class="flex flex-col items-center md:items-end gap-3 w-full md:w-auto relative z-10">
                <div class="text-center md:text-right">
                    <p class="text-[10px] text-indigo-600/60 font-black uppercase tracking-wider mb-1">Assigned Fee</p>
                    <p class="text-3xl font-black text-slate-800">Rs. ${feeData.structure.monthly_fee}</p>
                </div>
                ${isUpdate ? `
                    <div class="px-6 py-2 bg-emerald-100 text-emerald-700 rounded-full font-black text-xs uppercase tracking-widest flex items-center gap-2 border-2 border-emerald-200 animate-pulse">
                        <i class="fas fa-check-circle"></i> Already Paid
                    </div>
                ` : `
                    <div class="px-6 py-2 bg-amber-100 text-amber-700 rounded-full font-black text-xs uppercase tracking-widest flex items-center gap-2 border-2 border-amber-200">
                        <i class="fas fa-clock"></i> Not Paid
                    </div>
                `}
            </div>
        </div>

        <form id="fee_collection_form" class="space-y-6">
            <input type="hidden" name="gr_no" value="${student.gr_no}">
            <input type="hidden" name="transaction_id" value="${existing ? existing.id : ''}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Fee for Month of</label>
                        <div class="relative group">
                            <i class="fas fa-calendar-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-indigo-500 transition"></i>
                            <input type="month" name="month_for" id="fee_month_picker" 
                                   value="${existing ? existing.month_for : (document.getElementById('fee_month_picker')?.value || new Date().toISOString().slice(0, 7))}" 
                                   onchange="checkFeeStatusForMonth('${student.gr_no}')"
                                   class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-indigo-500 focus:bg-white transition-all outline-none font-bold text-gray-800" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Amount Paid This Session</label>
                        <div class="relative group">
                            <i class="fas fa-money-bill-wave absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-emerald-500 transition"></i>
                            <input type="number" id="amount_paid_input"
                                   value="${existing ? existing.amount_paid : 0}"
                                   oninput="recalculateTotal()"
                                   min="0" step="1"
                                   class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-emerald-500 focus:bg-white transition-all outline-none font-bold text-emerald-600 text-lg"
                                   placeholder="Enter amount student paid">
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Fee Breakdown</label>
                    <div id="fees_breakdown_list" class="space-y-3">
                        <!-- Base Tuition Fee (Always there) -->
                        <div class="flex items-center gap-3 p-3 bg-white border-2 border-slate-100 rounded-xl shadow-sm">
                            <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-500">
                                <i class="fas fa-book-reader"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">Tuition Fee</p>
                                <p class="text-sm font-black text-gray-800">Standard Month</p>
                            </div>
                            <div class="w-24">
                                <input type="number" name="tuition_fee" class="fee-due-input w-full bg-transparent border-none text-right font-black text-lg focus:ring-0 text-gray-800"
                                       value="${existing ? (parseFloat(existing.tuition_fee) > 0 ? parseFloat(existing.tuition_fee) : feeData.structure.monthly_fee) : feeData.structure.monthly_fee}"
                                       oninput="recalculateTotal()" data-type="tuition" min="0" step="1">
                            </div>
                            <div class="w-6"></div>
                        </div>
                        
                        <!-- Previous Month Debt (If exists) -->
                        ${feeData.previous_debt > 0 ? `
                        <div onclick="showArrearsBreakdown()" class="flex items-center gap-3 p-3 bg-amber-50 border-2 border-amber-200 rounded-xl shadow-sm cursor-pointer hover:bg-amber-100 hover:border-amber-300 transition-all group/arrears" title="Click to view month-wise breakdown">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 group-hover/arrears:scale-110 transition-transform">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-amber-700 font-bold uppercase tracking-tighter flex items-center gap-1">
                                    Previous Arrears
                                    <i class="fas fa-external-link-alt text-[8px] opacity-50 group-hover/arrears:opacity-100"></i>
                                </p>
                                <p class="text-xs font-bold text-amber-500">Click to see which months are pending</p>
                            </div>
                            <div class="w-24 text-right pr-3 font-black text-lg text-amber-700">
                                Rs. ${Number(feeData.previous_debt).toLocaleString()}
                            </div>
                            <div class="w-6 flex items-center justify-center text-amber-400 group-hover/arrears:text-amber-600">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </div>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Quick Add Toggles -->
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-dashed border-gray-200">
                        <button type="button" id="btn_toggle_admission"
                                onclick="toggleBreakdownField('admission', 'Admission Fee', ${feeData.structure.admission_fee}, 'fa-user-plus', 'indigo', this)" 
                                class="fee-toggle-btn px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-indigo-100 transition-all border-2 border-indigo-100 flex items-center gap-2 shadow-sm"
                                data-selected="false">
                            <i class="fas fa-plus-circle opacity-40"></i> Admission
                        </button>
                        
                        <button type="button" id="btn_toggle_exam"
                                onclick="toggleBreakdownField('exam', 'Exam Fee', ${feeData.structure.exam_fee}, 'fa-file-invoice', 'amber', this)" 
                                class="fee-toggle-btn px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-amber-100 transition-all border-2 border-amber-100 flex items-center gap-2 shadow-sm"
                                data-selected="false">
                            <i class="fas fa-plus-circle opacity-40"></i> Exam Fee
                        </button>
                        
                        <button type="button" id="btn_toggle_other"
                                onclick="toggleBreakdownField('other', 'Other Fee', 0, 'fa-ellipsis-h', 'purple', this)" 
                                class="fee-toggle-btn px-4 py-2 bg-purple-50 text-purple-700 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-purple-100 transition-all border-2 border-purple-100 flex items-center gap-2 shadow-sm"
                                data-selected="false">
                            <i class="fas fa-plus-circle opacity-40"></i> Others
                        </button>
                    </div>
                    </div>

                    <!-- Total & Debt Display -->
                    <div class="bg-slate-900 rounded-2xl p-6 mt-6 relative overflow-hidden group shadow-2xl">
                        <div class="absolute right-[-10px] top-[-10px] text-gray-800 opacity-20 text-7xl select-none group-hover:rotate-12 transition-transform">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="relative z-10 grid grid-cols-3 gap-4 divide-x divide-slate-800">
                            <div class="pr-2">
                                <p class="text-indigo-400 text-[9px] font-black uppercase tracking-[0.1em] mb-1">Total Dues</p>
                                <p class="text-white text-xl font-black tracking-tight">
                                    <span class="text-indigo-500 text-xs font-bold uppercase">Rs.</span>
                                    <span id="total_dues_display">0.00</span>
                                </p>
                            </div>
                            <div class="px-2">
                                <p class="text-emerald-400 text-[9px] font-black uppercase tracking-[0.1em] mb-1">Amount Paid</p>
                                <p class="text-white text-xl font-black tracking-tight">
                                    <span class="text-emerald-500 text-xs font-bold uppercase">Rs.</span>
                                    <span id="total_amount_display">0.00</span>
                                </p>
                            </div>
                            <div class="pl-2">
                                <p class="text-amber-400 text-[9px] font-black uppercase tracking-[0.1em] mb-1">Remaining Debt</p>
                                <p class="text-white text-xl font-black tracking-tight">
                                    <span class="text-amber-500 text-xs font-bold uppercase">Rs.</span>
                                    <span id="remaining_debt_display">0.00</span>
                                </p>
                            </div>
                        </div>
                        <input type="hidden" name="amount_paid" id="total_amount_input" value="0">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Discount (Optional)</label>
                    <div class="relative group">
                        <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-red-500 transition"></i>
                        <input type="number" name="discount" value="${existing ? (existing.discount || 0) : 0}" oninput="recalculateTotal()" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-red-500 focus:bg-white transition-all outline-none font-bold text-red-600">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Payment Method</label>
                    <div class="relative group">
                        <i class="fas fa-credit-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-indigo-500 transition"></i>
                        <select name="payment_method" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-indigo-500 focus:bg-white transition-all outline-none font-bold text-gray-800 appearance-none cursor-pointer">
                            <option value="Cash" ${existing && existing.payment_method === 'Cash' ? 'selected' : ''}>Cash Payment</option>
                            <option value="Bank Transfer" ${existing && existing.payment_method === 'Bank Transfer' ? 'selected' : ''}>Direct Bank Transfer</option>
                            <option value="Online" ${existing && existing.payment_method === 'Online' ? 'selected' : ''}>Online Payment</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Remarks</label>
                <div class="relative group">
                    <i class="fas fa-comment-dots absolute left-4 top-6 text-gray-400 group-hover:text-indigo-500 transition"></i>
                    <textarea name="notes" rows="3" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-indigo-500 focus:bg-white transition-all outline-none font-medium text-gray-700" placeholder="e.g. Late fee, previous balance note, special discount reason...">${existing ? (existing.notes || '') : ''}</textarea>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="fillFullPayment()" class="flex-1 bg-indigo-50 text-indigo-700 font-black py-4 rounded-2xl hover:bg-indigo-100 transition-all border-2 border-indigo-200 flex items-center justify-center gap-2 uppercase tracking-widest text-xs">
                    <i class="fas fa-check-double"></i> Received Full
                </button>
                <button type="button" onclick="clearAllFees()" class="flex-1 bg-slate-50 text-slate-500 font-black py-4 rounded-2xl hover:bg-slate-100 transition-all border-2 border-slate-200 flex items-center justify-center gap-2 uppercase tracking-widest text-xs">
                    <i class="fas fa-undo"></i> No Fee
                </button>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full group/btn relative ${isUpdate ? 'bg-indigo-600' : 'bg-emerald-600'} text-white font-black py-5 rounded-2xl hover:translate-y-[-4px] active:translate-y-0 transition-all shadow-xl ${isUpdate ? 'shadow-indigo-100' : 'shadow-emerald-100'} flex items-center justify-center gap-3 text-lg tracking-widest uppercase">
                    <i class="fas ${isUpdate ? 'fa-edit' : 'fa-hand-holding-usd'} text-xl group-hover/btn:rotate-12 transition-transform"></i>
                    ${isUpdate ? 'Update Payment Record' : 'Confirm Collection & Print'}
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-black/10 rounded-b-2xl"></div>
                </button>
            </div>
        </form>
    `;

    document.getElementById('fee_collection_form').onsubmit = handleCollection;
    
    // Auto-toggle breakdown fields for existing payment
    if (existing) {
        if (parseFloat(existing.admission_fee) > 0) {
            toggleBreakdownField('admission', 'Admission Fee', existing.admission_fee, 'fa-user-plus', 'indigo', document.getElementById('btn_toggle_admission'));
        }
        if (parseFloat(existing.exam_fee) > 0) {
            toggleBreakdownField('exam', 'Exam Fee', existing.exam_fee, 'fa-file-invoice', 'amber', document.getElementById('btn_toggle_exam'));
        }
        if (parseFloat(existing.other_fee) > 0) {
            toggleBreakdownField('other', 'Other Fee', existing.other_fee, 'fa-ellipsis-h', 'slate', document.getElementById('btn_toggle_other'));
            const labelInput = document.querySelector('input[name="other_label"]');
            if (labelInput) labelInput.value = existing.other_label || 'Other Charges';
        }
    }

    // Force immediate recalculation
    setTimeout(recalculateTotal, 50);
}

window.checkFeeStatusForMonth = function(gr_no) {
    const month = document.getElementById('fee_month_picker').value;
    const container = document.getElementById('collection_details');
    
    // Add a slight overlay to indicate loading
    container.classList.add('opacity-50');
    
    fetch(`../api/get_fee_status.php?gr_no=${gr_no}&month=${month}`)
        .then(res => res.json())
        .then(data => {
            container.classList.remove('opacity-50');
            renderCollectionForm(data.student, data);
        });
}

window.addToTotalAmount = function(amt) {
    const input = document.getElementById('amount_paid_input');
    if (input) {
        let current = parseInt(input.value) || 0;
        input.value = current + parseInt(amt);
        // Visual feedback
        input.classList.add('ring-4', 'ring-emerald-500/20');
        setTimeout(() => input.classList.remove('ring-4', 'ring-emerald-500/20'), 500);
    }
}

window.addOtherFee = function() {
    const amount = prompt("Enter custom amount to add:", "0");
    if (amount !== null && !isNaN(amount) && amount !== "") {
        window.addToTotalAmount(amount);
    }
}

window.toggleBreakdownField = function(id, label, defaultAmt, icon, color, btn) {
    const list = document.getElementById('fees_breakdown_list');
    const isSelected = btn.getAttribute('data-selected') === 'true';
    const newStatus = !isSelected;
    
    btn.setAttribute('data-selected', newStatus);
    
    if (newStatus) {
        // Add Field
        const div = document.createElement('div');
        div.id = `fee_row_${id}`;
        div.className = "flex items-center gap-3 p-3 bg-white border-2 border-slate-100 rounded-xl shadow-sm animate-in slide-in-from-left-4 duration-300";
        div.innerHTML = `
            <div class="w-10 h-10 bg-${color}-50 rounded-lg flex items-center justify-center text-${color}-500">
                <i class="fas ${icon}"></i>
            </div>
            <div class="flex-1">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">${label}</p>
                ${id === 'other' ? `<input type="text" name="other_label" class="text-[10px] font-bold text-slate-400 bg-transparent border-none p-0 focus:ring-0 w-full" value="Other Charges" placeholder="Enter Label...">` : `<p class="text-xs font-bold text-gray-400">Standard ${label}</p>`}
            </div>
            <div class="w-24">
                <input type="number" name="${id}_fee" class="fee-due-input w-full bg-transparent border-none text-right font-black text-lg focus:ring-0" 
                       value="${defaultAmt}" oninput="recalculateTotal()" data-type="${id}">
            </div>
            <button type="button" onclick="toggleBreakdownField('${id}', '', 0, '', '', document.getElementById('btn_toggle_${id}'))" class="w-6 h-6 flex items-center justify-center text-red-300 hover:text-red-500 transition-colors">
                <i class="fas fa-times-circle"></i>
            </button>
        `;
        list.appendChild(div);
        
        // Update Button
        btn.classList.add(`bg-${color}-600`, 'text-white', `border-${color}-600`);
        btn.classList.remove(`bg-${color}-50`, `text-${color}-700`, `border-${color}-100`);
        btn.querySelector('i').classList.replace('fa-plus-circle', 'fa-check-circle');
    } else {
        // Remove Field
        const row = document.getElementById(`fee_row_${id}`);
        if(row) row.remove();
        
        // Update Button
        btn.classList.remove(`bg-${color}-600`, 'text-white', `border-${color}-600`);
        btn.classList.add(`bg-${color}-50`, `text-${color}-700`, `border-${color}-100`);
        btn.querySelector('i').classList.replace('fa-check-circle', 'fa-plus-circle');
    }
    
    recalculateTotal();
}

function recalculateTotal() {
    let currentDues = 0;
    
    // Tuition due
    const tuitionInput = document.querySelector('input[name="tuition_fee"]');
    if (tuitionInput) {
        currentDues += parseInt(tuitionInput.value) || 0;
    }
    
    // Admission due (if toggle active)
    const admissionBtn = document.getElementById('btn_toggle_admission');
    if (admissionBtn && admissionBtn.getAttribute('data-selected') === 'true') {
        const admissionInput = document.querySelector('input[name="admission_fee"]');
        if (admissionInput) {
            currentDues += parseInt(admissionInput.value) || 0;
        }
    }
    
    // Exam due (if toggle active)
    const examBtn = document.getElementById('btn_toggle_exam');
    if (examBtn && examBtn.getAttribute('data-selected') === 'true') {
        const examInput = document.querySelector('input[name="exam_fee"]');
        if (examInput) {
            currentDues += parseInt(examInput.value) || 0;
        }
    }
    
    // Other due (if toggle active)
    const otherBtn = document.getElementById('btn_toggle_other');
    if (otherBtn && otherBtn.getAttribute('data-selected') === 'true') {
        const otherInput = document.querySelector('input[name="other_fee"]');
        if (otherInput) {
            currentDues += parseInt(otherInput.value) || 0;
        }
    }

    // Subtract discount
    const discountInput = document.querySelector('input[name="discount"]');
    let discount = 0;
    if (discountInput) {
        discount = parseInt(discountInput.value) || 0;
    }
    currentDues -= discount;
    if (currentDues < 0) currentDues = 0;

    // Get previous debt (loaded from the server and stored in feeData)
    const feeData = window.currentStudentFeeData;
    const previousDebt = feeData && feeData.previous_debt ? parseFloat(feeData.previous_debt) : 0;

    // Total Dues is current month's dues + previous debt
    const totalDues = currentDues + previousDebt;

    // Get actually paid amount from the input field
    const amountPaidInput = document.getElementById('amount_paid_input');
    let totalPaid = amountPaidInput ? parseInt(amountPaidInput.value) || 0 : 0;

    // Enforce: Total Paid cannot exceed Total Dues!
    if (totalPaid > totalDues) {
        totalPaid = totalDues;
        if (amountPaidInput) {
            amountPaidInput.value = totalPaid;
        }
    }

    const remainingDebt = Math.max(0, totalDues - totalPaid);

    const displayPaid = document.getElementById('total_amount_display');
    const displayDues = document.getElementById('total_dues_display');
    const displayDebt = document.getElementById('remaining_debt_display');
    const hiddenInput = document.getElementById('total_amount_input');
    
    if (displayPaid) displayPaid.innerText = totalPaid.toLocaleString();
    if (displayDues) displayDues.innerText = totalDues.toLocaleString();
    if (displayDebt) displayDebt.innerText = remainingDebt.toLocaleString();
    if (hiddenInput) hiddenInput.value = totalPaid;
}

window.confirmDeletion = function(id, month) {
    if (confirm(`Are you sure you want to delete the payment for ${month}? This cannot be undone.`)) {
        fetch(`../api/delete_fee_payment.php?id=${id}`, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Record deleted successfully!');
                    location.reload(); // Refresh to update all stats
                } else {
                    alert(data.error || 'Failed to delete record.');
                }
            });
    }
}

// Global initialization in renderCollectionForm
setTimeout(recalculateTotal, 100);


function handleCollection(e) {
    e.preventDefault();

    // Sync visible amount input → hidden form field before submitting
    const visibleInput = document.getElementById('amount_paid_input');
    const hiddenInput  = document.getElementById('total_amount_input');
    const totalPaid    = visibleInput ? (parseInt(visibleInput.value) || 0) : 0;

    if (totalPaid <= 0) {
        alert('Please enter the amount paid by the student (must be greater than 0).');
        if (visibleInput) visibleInput.focus();
        return;
    }

    if (hiddenInput) hiddenInput.value = totalPaid;

    const formData = new FormData(this);

    fetch('../api/collect_fee.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.open(`print_receipt.php?id=${data.transaction_id}`, '_blank');
            location.reload();
        } else {
            alert(data.error || 'Failed to record payment');
        }
    });
}


let historySortMode = 'paid_first';

function updateHistorySortButton() {
    const btn = document.getElementById('history_sort_unpaid_btn');
    if (!btn) return;
    if (historySortMode === 'unpaid_first') {
        btn.classList.add('bg-red-600', 'text-white', 'border-red-600', 'shadow-md');
        btn.classList.remove('bg-red-50', 'text-red-600', 'border-red-200');
    } else {
        btn.classList.remove('bg-red-600', 'text-white', 'border-red-600', 'shadow-md');
        btn.classList.add('bg-red-50', 'text-red-600', 'border-red-200');
    }
}

function toggleHistorySortUnpaid() {
    historySortMode = historySortMode === 'unpaid_first' ? 'paid_first' : 'unpaid_first';
    updateHistorySortButton();
    loadHistory();
}

function getHistoryFilterParams() {
    return {
        month: document.getElementById('history_month').value,
        class: document.getElementById('history_class').value,
        stage: document.getElementById('history_stage').value,
        search: document.getElementById('history_search').value
    };
}

function toggleHistoryDownloadMenu() {
    const menu = document.getElementById('history_download_menu');
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('history_download_wrap');
    const menu = document.getElementById('history_download_menu');
    if (wrap && menu && !wrap.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

function downloadFeeHistory(format) {
    const f = getHistoryFilterParams();
    const params = new URLSearchParams({
        month: f.month,
        class: f.class,
        stage: f.stage,
        search: f.search
    });
    document.getElementById('history_download_menu')?.classList.add('hidden');

    if (format === 'pdf') {
        window.open(`print_fee_collection_report.php?${params.toString()}`, '_blank');
    } else {
        window.location.href = `../api/export_fee_history.php?${params.toString()}`;
    }
}

function loadHistory() {
    const f = getHistoryFilterParams();
    const container = document.getElementById('history_table_container');
    
    let loadingMsg = f.month ? `Loading records for ${f.month}...` : 'Loading all-time records...';
    container.innerHTML = `<div class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> ${loadingMsg}</div>`;
    
    fetch(`../api/get_fee_history.php?month=${f.month}&class=${encodeURIComponent(f.class)}&stage=${encodeURIComponent(f.stage)}&search=${encodeURIComponent(f.search)}&sort=${historySortMode}`)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}

window.showClassDetail = function(className) {
    const modal = document.getElementById('class_detail_modal');
    const content = document.getElementById('modal_content');
    const title = document.getElementById('modal_title');
    const subtitle = document.getElementById('modal_subtitle');
    
    // Parse selected month for labels
    const [year, month] = OVERVIEW_MONTH.split('-');
    const dateObj = new Date(year, month - 1);
    const monthName = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(dateObj);
    
    title.innerText = `Class: ${className}`;
    subtitle.innerText = `Payment summary for ${monthName} ${year}`;
    content.innerHTML = '<div class="py-12 text-center"><i class="fas fa-circle-notch fa-spin fa-2x text-indigo-500"></i><p class="mt-2 text-gray-400">Fetching student list...</p></div>';
    
    const dialog = document.getElementById('fee_modal_dialog');
    if (dialog) {
        dialog.classList.remove('sm:max-w-4xl');
        dialog.classList.add('sm:max-w-2xl');
    }
    if (modal) {
        modal.style.display = 'block';
        modal.classList.remove('hidden');
    }

    fetch(`../api/get_class_fee_status.php?class=${encodeURIComponent(className)}&month=${OVERVIEW_MONTH}`)
        .then(res => res.json())
        .then(response => {
            const data = response.data || [];
            if (data.length === 0) {
                content.innerHTML = '<p class="py-8 text-center text-gray-500 italic">No students found in this class.</p>';
                return;
            }

            let html = '<div class="grid grid-cols-1 gap-3">';
            data.forEach(s => {
                const isPaid = s.status === 'Paid';
                const isPartial = s.status === 'Partial';
                
                let iconClass = 'fa-clock';
                let iconBg = 'bg-red-50 text-red-600';
                let badgeClass = 'bg-red-100 text-red-700';
                let statusLabel = s.status;
                
                if (isPaid) {
                    iconClass = 'fa-check-circle';
                    iconBg = 'bg-green-50 text-green-600';
                    badgeClass = 'bg-green-100 text-green-700';
                } else if (isPartial) {
                    iconClass = 'fa-exclamation-triangle';
                    iconBg = 'bg-amber-50 text-amber-600';
                    badgeClass = 'bg-amber-100 text-amber-700';
                    statusLabel = `Partial (Dues: Rs. ${s.debt})`;
                } else {
                    statusLabel = `Unpaid (Dues: Rs. ${s.debt})`;
                }

                html += `
                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition shadow-sm bg-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-lg ${iconBg}">
                                <i class="fas ${iconClass}"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${s.student_name}</div>
                                <div class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">GR: ${s.gr_no}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${badgeClass}">
                                ${statusLabel}
                            </span>
                            ${s.status !== 'Paid' ? `
                            <button onclick="pickStudent('${s.gr_no}', '${s.student_name}')" class="bg-indigo-600 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition flex items-center gap-1.5 whitespace-nowrap">
                                <i class="fas fa-hand-holding-usd"></i> Collect
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            content.innerHTML = html;
        });
}

window.showStudentHistory = function(gr, name) {
    showStudentFeeProfile(gr, name);
}

window.showArrearsBreakdown = function() {
    const feeData = window.currentStudentFeeData;
    if (!feeData || !feeData.previous_debt || feeData.previous_debt <= 0) return;

    const breakdown = feeData.previous_debt_breakdown || [];
    const student = feeData.student || {};
    const monthPicker = document.getElementById('fee_month_picker');
    const targetMonth = monthPicker ? monthPicker.value : new Date().toISOString().slice(0, 7);
    const targetLabel = new Date(targetMonth + '-01').toLocaleString('en-US', { month: 'long', year: 'numeric' });

    const modal = document.getElementById('class_detail_modal');
    const content = document.getElementById('modal_content');
    const title = document.getElementById('modal_title');
    const subtitle = document.getElementById('modal_subtitle');
    const dialog = document.getElementById('fee_modal_dialog');

    title.innerText = 'Previous Arrears Breakdown';
    subtitle.innerText = `${student.student_name || 'Student'} (GR: ${student.gr_no || '—'}) — before ${targetLabel}`;

    let rowsHtml = '';
    if (breakdown.length === 0) {
        rowsHtml = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 italic">No month-wise breakdown available.</td></tr>';
    } else {
        breakdown.forEach(row => {
            const monthLabel = new Date(row.month + '-01').toLocaleString('en-US', { month: 'long', year: 'numeric' });
            const isUnpaid = row.status === 'unpaid';
            const statusBadge = isUnpaid
                ? '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase">Unpaid</span>'
                : '<span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold uppercase">Partial</span>';

            rowsHtml += `
                <tr class="hover:bg-amber-50/50 transition">
                    <td class="px-4 py-3 font-bold text-gray-800">${monthLabel}</td>
                    <td class="px-4 py-3 text-right text-gray-600">Rs. ${Number(row.due).toLocaleString()}</td>
                    <td class="px-4 py-3 text-right text-emerald-600">Rs. ${Number(row.paid).toLocaleString()}</td>
                    <td class="px-4 py-3 text-right font-black text-amber-700">Rs. ${Number(row.balance).toLocaleString()}</td>
                    <td class="px-4 py-3 text-center">${statusBadge}</td>
                </tr>
            `;
        });
    }

    content.innerHTML = `
        <div class="mb-4 p-4 bg-amber-50 rounded-xl border border-amber-200 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black uppercase text-amber-600 tracking-wider">Total Previous Arrears</p>
                <p class="text-2xl font-black text-amber-800">Rs. ${Number(feeData.previous_debt).toLocaleString()}</p>
            </div>
            <div class="text-right text-xs text-amber-600 font-medium">
                <p>${breakdown.length} month(s) pending</p>
                <p class="text-[10px] text-amber-500 mt-0.5">Before ${targetLabel}</p>
            </div>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold">
                    <tr>
                        <th class="px-4 py-3">Month</th>
                        <th class="px-4 py-3 text-right">Due</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${rowsHtml}</tbody>
                ${breakdown.length > 0 ? `
                <tfoot class="bg-amber-50/80 font-black text-amber-800">
                    <tr>
                        <td class="px-4 py-3" colspan="3">Total Arrears</td>
                        <td class="px-4 py-3 text-right">Rs. ${Number(feeData.previous_debt).toLocaleString()}</td>
                        <td></td>
                    </tr>
                </tfoot>` : ''}
            </table>
        </div>
        <p class="mt-3 text-[10px] text-gray-400 italic text-center">Click a month in Collect Fee to record payment for that period.</p>
    `;

    if (dialog) {
        dialog.classList.remove('sm:max-w-2xl');
        dialog.classList.add('sm:max-w-3xl');
    }
    if (modal) {
        modal.style.display = 'block';
        modal.classList.remove('hidden');
    }
};

window.closeFeeModal = function() {
    const modal = document.getElementById('class_detail_modal');
    const dialog = document.getElementById('fee_modal_dialog');
    if (dialog) {
        dialog.classList.remove('sm:max-w-4xl', 'sm:max-w-3xl', 'sm:max-w-lg');
        dialog.classList.add('sm:max-w-2xl');
    }
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
};

// Add ESC key listener to document
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.closeFeeModal();
    }
});





function loadDefaulters() {
    const month = document.getElementById('defaulter_month').value;
    const container = document.getElementById('defaulters_table_container');
    container.innerHTML = '<div class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading list...</div>';
    
    fetch(`../api/get_defaulters.php?month=${month}`)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}

function pickStudent(gr, name) {
    switchTab('collect');
    studentSearch.value = name;
    selectStudent({ gr_no: gr, student_name: name });
}

function loadClassStudents() {
    const className = document.getElementById('class_selector').value;
    const container = document.getElementById('class_students_list');
    
    if (!className) {
        container.innerHTML = '<p class="text-xs text-gray-400 text-center py-8">Select a class to see student list.</p>';
        return;
    }

    container.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-indigo-500"></i></div>';
    
    fetch(`../api/get_class_fee_status.php?class=${encodeURIComponent(className)}`)
        .then(res => res.json())
        .then(response => {
            const data = response.data || [];
            if (data.length === 0) {
                container.innerHTML = '<p class="text-xs text-gray-500 text-center py-8">No active students in this class.</p>';
                return;
            }

            container.innerHTML = '';
            data.forEach(s => {
                const isPaid = s.status === 'Paid';
                const isPartial = s.status === 'Partial';
                
                let rowBg = 'bg-red-50/30';
                let badgeClass = 'bg-red-100 text-red-700';
                let statusLabel = s.status;
                
                if (isPaid) {
                    rowBg = 'bg-green-50/30';
                    badgeClass = 'bg-green-100 text-green-700';
                } else if (isPartial) {
                    rowBg = 'bg-amber-50/30';
                    badgeClass = 'bg-amber-100 text-amber-700';
                    statusLabel = `Partial`;
                } else {
                    statusLabel = `Unpaid`;
                }

                const div = document.createElement('div');
                div.className = `flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-indigo-200 transition cursor-pointer ${rowBg}`;
                div.innerHTML = `
                    <div class="flex-1">
                        <div class="text-sm font-bold text-gray-800">${s.student_name}</div>
                        <div class="text-[10px] text-gray-500">GR: ${s.gr_no} ${isPartial ? `&bull; Dues: Rs. ${s.debt}` : ''}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${badgeClass}">
                            ${statusLabel}
                        </span>
                        <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                    </div>
                `;
                div.onclick = () => selectStudent(s);
                container.appendChild(div);
            });
        });
}

// Initial loads
document.addEventListener('DOMContentLoaded', () => {
    loadHistory();
    loadDefaulters();
    // Default the history month to current
    document.getElementById('history_month').value = new Date().toISOString().slice(0, 7);
});
// Stage-based class filtering
function filterClassesByStage() {
    const stage = document.getElementById('stage_selector').value;
    const classSelector = document.getElementById('class_selector');
    const options = classSelector.querySelectorAll('option');
    
    classSelector.value = '';
    options.forEach(opt => {
        if (!opt.value) return; // Skip -- Select Class --
        if (!stage || opt.dataset.stage === stage) {
            opt.classList.remove('hidden');
        } else {
            opt.classList.add('hidden');
        }
    });
}

function filterHistoryClasses() {
    const stage = document.getElementById('history_stage').value;
    const classSelector = document.getElementById('history_class');
    const options = classSelector.querySelectorAll('option');
    
    classSelector.value = '';
    options.forEach(opt => {
        if (!opt.value) return;
        if (!stage || opt.dataset.stage === stage) {
            opt.classList.remove('hidden');
        } else {
            opt.classList.add('hidden');
        }
    });
    loadHistory();
}

// Ensure loadDefaulters passes stage
const originalLoadDefaulters = window.loadDefaulters;
window.loadDefaulters = function() {
    const month = document.getElementById('defaulter_month').value;
    const stage = document.getElementById('defaulter_stage').value;
    const className = document.getElementById('defaulter_class').value;
    const search = document.getElementById('defaulter_search').value;
    const container = document.getElementById('defaulters_table_container');
    
    container.innerHTML = '<div class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading defaulters...</div>';
    
    fetch(`../api/get_defaulters.php?month=${month}&stage=${encodeURIComponent(stage)}&class=${encodeURIComponent(className)}&search=${encodeURIComponent(search)}`)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
            // Update badge count
            const badge = document.getElementById('defaulter_badge');
            if (badge) {
                const rowCount = container.querySelectorAll('tbody tr:not(.no-defaulters)').length;
                const isEmpty = container.querySelector('td[colspan="6"]');
                badge.innerText = isEmpty ? 0 : rowCount;
            }
        });
};

function filterDefaulterClasses() {
    const stage = document.getElementById('defaulter_stage').value;
    const classSelector = document.getElementById('defaulter_class');
    const options = classSelector.querySelectorAll('option');
    
    classSelector.value = '';
    options.forEach(opt => {
        if (!opt.value) return;
        if (!stage || opt.dataset.stage === stage) {
            opt.classList.remove('hidden');
        } else {
            opt.classList.add('hidden');
        }
    });
}

function fillFullPayment() {
    // We want the student to pay the TOTAL DUE amount (including previous debt).
    const totalDuesDisplay = document.getElementById('total_dues_display');
    const totalDues = totalDuesDisplay ? parseInt(totalDuesDisplay.innerText.replace(/[^\d]/g, '')) || 0 : 0;
    
    const amountPaidInput = document.getElementById('amount_paid_input');
    if (amountPaidInput) {
        amountPaidInput.value = totalDues;
    }
    
    recalculateTotal();
    
    // Visual feedback
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Applied Full';
    btn.classList.replace('bg-indigo-50', 'bg-indigo-600');
    btn.classList.replace('text-indigo-700', 'text-white');
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.replace('bg-indigo-600', 'bg-indigo-50');
        btn.classList.replace('text-white', 'text-indigo-700');
    }, 1000);
}

function clearAllFees() {
    const amountPaidInput = document.getElementById('amount_paid_input');
    if (amountPaidInput) {
        amountPaidInput.value = 0;
    }
    recalculateTotal();
}

function applyQuickDiscount(amt) {
    const input = document.querySelector('input[name="discount"]');
    if (input) {
        let current = parseInt(input.value) || 0;
        input.value = current + amt;
        recalculateTotal();
        
        // Flash red
        input.classList.add('ring-2', 'ring-red-500');
        setTimeout(() => input.classList.remove('ring-2', 'ring-red-500'), 500);
    }
}

// Autocomplete Keyboard Navigation Support
function initAutocompleteKeyboardNavigation(inputEl, resultsEl) {
    if (!inputEl || !resultsEl) return;
    let activeIndex = -1;

    // Reset selection index whenever the dropdown contents change
    const observer = new MutationObserver(() => {
        activeIndex = -1;
    });
    observer.observe(resultsEl, { childList: true });

    inputEl.addEventListener('keydown', function(e) {
        if (resultsEl.classList.contains('hidden')) return;

        // Filter out informational or loading elements from navigation
        const items = Array.from(resultsEl.children).filter(item => {
            return !item.classList.contains('italic') && !item.textContent.includes('No students') && !item.innerHTML.includes('fa-spinner');
        });

        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex++;
            if (activeIndex >= items.length) activeIndex = 0;
            updateHighlight(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex--;
            if (activeIndex < 0) activeIndex = items.length - 1;
            updateHighlight(items);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && activeIndex < items.length) {
                e.preventDefault();
                const activeItem = items[activeIndex];
                // Select either the inner clickable section (overview) or the row itself
                const clickable = activeItem.querySelector('.cursor-pointer') || activeItem;
                clickable.click();
            }
        } else if (e.key === 'Escape') {
            resultsEl.classList.add('hidden');
            inputEl.blur();
        }
    });

    function updateHighlight(items) {
        items.forEach((item, idx) => {
            if (idx === activeIndex) {
                item.classList.add('bg-indigo-100', 'ring-2', 'ring-indigo-200');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('bg-indigo-100', 'ring-2', 'ring-indigo-200');
            }
        });
    }
}

// Initialize navigation on both autocomplete search boxes
initAutocompleteKeyboardNavigation(
    document.getElementById('student_search'),
    document.getElementById('search_results')
);
initAutocompleteKeyboardNavigation(
    document.getElementById('overview_student_search'),
    document.getElementById('overview_search_results')
);
</script>

<?php include '../includes/footer.php'; ?>
