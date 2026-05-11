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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Fee Overview</h2>
                <p class="text-xs text-gray-500">Viewing statistics for <?php echo date('F Y', strtotime($selectedMonth)); ?></p>
            </div>
            <form method="GET" class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-initial">
                    <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400 text-xs"></i>
                    <input type="month" name="overview_month" value="<?php echo $selectedMonth; ?>" onchange="this.form.submit()" class="w-full sm:w-auto border-2 border-gray-100 rounded-lg pl-9 pr-4 py-2 focus:border-indigo-500 outline-none font-bold text-gray-700 bg-gray-50/50 transition-all hover:border-indigo-200">
                </div>
                <noscript><button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm">Apply</button></noscript>
            </form>
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
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="student_search" class="pl-10 block w-full border border-gray-300 rounded-lg py-3 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Type to search...">
                                <div id="search_results" class="absolute z-10 w-full bg-white mt-1 border border-gray-200 rounded-lg shadow-xl hidden max-h-60 overflow-y-auto"></div>
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
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">All Collections</h3>
                <div class="flex items-center gap-2">
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
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
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

if (studentSearch) {
    studentSearch.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        fetch(`../api/get_students.php?search=${encodeURIComponent(query)}&json=1`)
            .then(res => res.json())
            .then(data => {
                const students = data.students;
                if (students && students.length > 0) {
                    searchResults.innerHTML = '';
                    students.forEach(s => {
                        const div = document.createElement('div');
                        div.className = 'px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0';
                        div.innerHTML = `
                            <div class="font-bold text-gray-800">${s.student_name}</div>
                            <div class="text-xs text-gray-500 uppercase">GR: ${s.gr_no} | Class: ${s.current_class}</div>
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
            renderCollectionForm({ gr_no: gr_no, student_name: name }, data);
        });
}

function renderCollectionForm(student, feeData) {
    const container = document.getElementById('collection_details');
    const existing = feeData.existing_payment;
    const isUpdate = !!existing;
    
    // Set base fee for recalculation
    currentBaseFee = parseInt(feeData.structure.monthly_fee) || 0;

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
                                <p class="text-sm font-black text-gray-800">${feeData.month_name || 'Standard Month'}</p>
                            </div>
                            <div class="w-24">
                                <input type="number" name="tuition_fee" class="fee-item-input w-full bg-transparent border-none text-right font-black text-lg focus:ring-0" 
                                       value="${existing ? (parseFloat(existing.amount_paid) - (parseFloat(existing.admission_fee) || 0) - (parseFloat(existing.exam_fee) || 0) - (parseFloat(existing.other_fee) || 0) + (parseFloat(existing.discount) || 0)) : feeData.structure.monthly_fee}" 
                                       oninput="recalculateTotal()" data-type="tuition">
                            </div>
                            <div class="w-6"></div>
                        </div>
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
                                onclick="toggleBreakdownField('other', 'Other Fee', 0, 'fa-ellipsis-h', 'slate', this)" 
                                class="fee-toggle-btn px-4 py-2 bg-slate-50 text-slate-600 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-slate-100 transition-all border-2 border-slate-200 flex items-center gap-2 shadow-sm"
                                data-selected="false">
                            <i class="fas fa-plus-circle opacity-40"></i> Others
                        </button>
                    </div>

                    <!-- Quick Discount Buttons -->
                    <div class="flex items-center gap-2 pt-2">
                        <p class="text-[9px] font-black text-gray-400 uppercase mr-1">Apply Discount:</p>
                        <button type="button" onclick="applyQuickDiscount(100)" class="px-3 py-1 bg-red-50 text-red-600 rounded-lg text-[10px] font-bold border border-red-100 hover:bg-red-600 hover:text-white transition-colors">-100</button>
                        <button type="button" onclick="applyQuickDiscount(200)" class="px-3 py-1 bg-red-50 text-red-600 rounded-lg text-[10px] font-bold border border-red-100 hover:bg-red-600 hover:text-white transition-colors">-200</button>
                        <button type="button" onclick="applyQuickDiscount(500)" class="px-3 py-1 bg-red-50 text-red-600 rounded-lg text-[10px] font-bold border border-red-100 hover:bg-red-600 hover:text-white transition-colors">-500</button>
                    </div>
                    </div>

                    <!-- Total Display -->
                    <div class="bg-slate-900 rounded-2xl p-5 mt-6 relative overflow-hidden group shadow-2xl">
                        <div class="absolute right-[-10px] top-[-10px] text-gray-800 opacity-20 text-7xl select-none group-hover:rotate-12 transition-transform">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Total Amount Paid</p>
                                <p class="text-white text-3xl font-black tracking-tight flex items-center gap-2">
                                    <span class="text-indigo-500 text-xl font-bold uppercase">Rs.</span>
                                    <span id="total_amount_display">0.00</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <i class="fas fa-money-bill-wave text-emerald-400 text-3xl opacity-50"></i>
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
                        <input type="number" name="discount" value="${existing ? (existing.discount || 0) : 0}" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-red-500 focus:bg-white transition-all outline-none font-bold text-red-600">
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
                <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest">Transaction Notes</label>
                <div class="relative group">
                    <i class="fas fa-comment-dots absolute left-4 top-6 text-gray-400 group-hover:text-indigo-500 transition"></i>
                    <textarea name="notes" rows="3" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-4 focus:border-indigo-500 focus:bg-white transition-all outline-none font-medium text-gray-700" placeholder="Enter any specific details or remarks...">${existing ? (existing.notes || '') : ''}</textarea>
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
                <input type="number" name="${id}_fee" class="fee-item-input w-full bg-transparent border-none text-right font-black text-lg focus:ring-0" 
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
    let total = 0;
    document.querySelectorAll('.fee-item-input').forEach(input => {
        total += parseInt(input.value) || 0;
    });
    
    const display = document.getElementById('total_amount_display');
    const hiddenInput = document.getElementById('total_amount_input');
    
    if (display) display.innerText = total.toLocaleString();
    if (hiddenInput) hiddenInput.value = total;
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

function loadHistory() {
    const month = document.getElementById('history_month').value;
    const className = document.getElementById('history_class').value;
    const container = document.getElementById('history_table_container');
    
    let loadingMsg = month ? `Loading records for ${month}...` : 'Loading all-time records...';
    container.innerHTML = `<div class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> ${loadingMsg}</div>`;
    
    fetch(`../api/get_fee_history.php?month=${month}&class=${encodeURIComponent(className)}`)
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
                html += `
                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition shadow-sm bg-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-lg ${isPaid ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'}">
                                <i class="fas ${isPaid ? 'fa-check-circle' : 'fa-clock'}"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${s.student_name}</div>
                                <div class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">GR: ${s.gr_no}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${isPaid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${s.status}
                            </span>
                            ${!isPaid ? `
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
    const modal = document.getElementById('class_detail_modal');
    const content = document.getElementById('modal_content');
    const title = document.getElementById('modal_title');
    const subtitle = document.getElementById('modal_subtitle');
    
    title.innerText = `Fee History: ${name}`;
    subtitle.innerText = `Full payment records for GR: ${gr}`;
    content.innerHTML = '<div class="py-12 text-center"><i class="fas fa-circle-notch fa-spin fa-2x text-indigo-500"></i><p class="mt-2 text-gray-400">Loading history...</p></div>';
    
    if (modal) {
        modal.style.display = 'block';
        modal.classList.remove('hidden');
    }

    fetch(`../api/get_fee_history.php?gr_no=${gr}`)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
        });
}

window.closeFeeModal = function() {

    const modal = document.getElementById('class_detail_modal');
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
                const div = document.createElement('div');
                div.className = `flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-indigo-200 transition cursor-pointer ${isPaid ? 'bg-green-50/30' : 'bg-red-50/30'}`;
                div.innerHTML = `
                    <div class="flex-1">
                        <div class="text-sm font-bold text-gray-800">${s.student_name}</div>
                        <div class="text-[10px] text-gray-500">GR: ${s.gr_no}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${isPaid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                            ${s.status}
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
    // 1. Ensure Tuition is at assigned value
    const tuitionInput = document.querySelector('input[name="tuition_fee"]');
    if (tuitionInput) {
        // We need to retrieve the assigned fee from the profile display
        const assignedTxt = document.querySelector('.text-3xl.font-black.text-slate-800').innerText;
        const assignedVal = parseInt(assignedTxt.replace(/[^\d]/g, '')) || 0;
        tuitionInput.value = assignedVal;
    }
    
    // 2. Clear discount
    const discountInput = document.querySelector('input[name="discount"]');
    if (discountInput) discountInput.value = 0;
    
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
    document.querySelectorAll('.fee-item-input').forEach(i => i.value = 0);
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
</script>

<?php include '../includes/footer.php'; ?>
