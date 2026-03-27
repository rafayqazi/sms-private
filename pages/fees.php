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
$feeStats = $db->getFeeStats();
$defaulters = $db->getDefaulters();

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
            <button onclick="switchTab('defaulters')" id="tab-defaulters" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Defaulters List
            </button>
        </nav>
    </div>

    <!-- Tab Contents -->
    <div id="content-overview" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                    </div>
                </div>
                <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">This Month's Collections</h3>
                <p class="text-2xl font-bold text-gray-900 mt-1">Rs. <?php echo number_format($feeStats['this_month'], 2); ?></p>
                <p class="text-xs text-green-600 mt-2"><i class="fas fa-check-circle"></i> Up to date</p>
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
                <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Monthly Defaulters</h3>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($defaulters); ?></p>
                <p class="text-xs text-amber-600 mt-2">Pending for <?php echo date('F Y'); ?></p>
            </div>
        </div>
        
        <!-- Class-wise Breakdown -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 mt-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-indigo-500"></i> Monthly Collections by Class (<?php echo date('F'); ?>)
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
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800"><?php echo htmlspecialchars($sMap[$r['gr_no']] ?? 'Unknown'); ?></div>
                                    <div class="text-[10px] text-gray-500">GR: <?php echo $r['gr_no']; ?></div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-indigo-600"><?php echo $r['month_for']; ?></td>
                                <td class="px-6 py-4 font-bold">Rs. <?php echo number_format($r['amount_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $r['payment_date']; ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 bg-gray-100 rounded text-[10px] uppercase font-bold text-gray-600">
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
                    <select id="class_selector" onchange="loadClassStudents()" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class_name']; ?>"><?php echo $c['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>

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
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($classes as $class): 
                            $name = $class['class_name'];
                            $fees = $feeStructure[$name] ?? ['monthly_fee' => 0, 'admission_fee' => 0, 'exam_fee' => 0, 'updated_at' => '-'];
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-700"><?php echo htmlspecialchars($name); ?></td>
                            <td class="px-6 py-4">
                                <input type="number" name="fees[<?php echo $name; ?>][monthly]" value="<?php echo $fees['monthly_fee']; ?>" class="w-32 border border-gray-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="fees[<?php echo $name; ?>][admission]" value="<?php echo $fees['admission_fee']; ?>" class="w-32 border border-gray-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="fees[<?php echo $name; ?>][exam]" value="<?php echo $fees['exam_fee']; ?>" class="w-32 border border-gray-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-400"><?php echo $fees['updated_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <div id="content-history" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Full History Table -->
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">All Collections</h3>
                <div class="flex gap-2">
                    <select id="history_class" class="text-sm border rounded px-3 py-1" onchange="loadHistory()">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class_name']; ?>"><?php echo $c['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="month" id="history_month" class="text-sm border rounded px-3 py-1" onchange="loadHistory()">
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
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Defaulters List</h3>
                <div class="flex gap-2">
                    <input type="month" id="defaulter_month" class="text-sm border rounded px-3 py-1" value="<?php echo date('Y-m'); ?>" onchange="loadDefaulters()">
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

    fetch(`../api/get_fee_status.php?gr_no=${student.gr_no}`)
        .then(res => res.json())
        .then(data => {
            renderCollectionForm(student, data);
        });
}

function renderCollectionForm(student, feeData) {
    const container = document.getElementById('collection_details');
    // Implementation of form rendering here
    container.innerHTML = `
        <div class="bg-indigo-50 rounded-lg p-4 mb-6 flex items-center justify-between border border-indigo-100">
            <div>
                <p class="text-xs text-indigo-600 font-bold uppercase tracking-wider">Student Details</p>
                <p class="text-lg font-bold text-gray-900">${student.student_name}</p>
                <p class="text-sm text-gray-600">GR No: ${student.gr_no} | Class: ${student.current_class}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-indigo-600 font-bold uppercase tracking-wider">Class Fee</p>
                <p class="text-lg font-bold text-gray-900">Rs. ${feeData.structure.monthly_fee}</p>
            </div>
        </div>

        <form id="fee_collection_form" class="space-y-4">
            <input type="hidden" name="gr_no" value="${student.gr_no}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Month</label>
                    <input type="month" name="month_for" value="${new Date().toISOString().slice(0, 7)}" class="w-full border rounded-lg p-2.5" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Collect (Rs.)</label>
                    <input type="number" name="amount_paid" value="${feeData.structure.monthly_fee}" class="w-full border rounded-lg p-2.5 font-bold text-lg" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount (Optional)</label>
                    <input type="number" name="discount" value="0" class="w-full border rounded-lg p-2.5 text-red-600 font-medium">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full border rounded-lg p-2.5">
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Online">Online</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border rounded-lg p-2.5" placeholder="Any remarks..."></textarea>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition shadow-lg flex items-center justify-center gap-2">
                <i class="fas fa-file-invoice-dollar"></i> Confirm Collection & Print Receipt
            </button>
        </form>
    `;

    document.getElementById('fee_collection_form').onsubmit = handleCollection;
}

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
    container.innerHTML = '<div class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading history...</div>';
    
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
    
    const currentMonth = new Date().toISOString().slice(0, 7);
    const monthName = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(new Date());
    
    title.innerText = `Class: ${className}`;
    subtitle.innerText = `Payment summary for ${monthName} ${new Date().getFullYear()}`;
    content.innerHTML = '<div class="py-12 text-center"><i class="fas fa-circle-notch fa-spin fa-2x text-indigo-500"></i><p class="mt-2 text-gray-400">Fetching student list...</p></div>';
    
    if (modal) {
        modal.style.display = 'block';
        modal.classList.remove('hidden');
    }

    fetch(`../api/get_class_fee_status.php?class=${encodeURIComponent(className)}&month=${currentMonth}`)
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
</script>

<?php include '../includes/footer.php'; ?>
