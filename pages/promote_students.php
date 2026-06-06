<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('promote_students.php')) {
    header("Location: ../index.php");
    exit;
}
$db = new Database();

$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$students = [];

if ($selectedClass) {
    $students = $db->getStudentsByClass($selectedClass);
}

// Build progression map for UI feedback (Section-Locked)
$allClasses = $db->getClasses();
$progressionMap = [];
$stageGroups = [];
foreach ($allClasses as $c) {
    $s = $c['stage'] ?? 'Elementary';
    if (!isset($stageGroups[$s])) $stageGroups[$s] = [];
    $stageGroups[$s][] = $c;
}

foreach ($stageGroups as $stage => $groupClasses) {
    for ($i = 0; $i < count($groupClasses); $i++) {
        $current = $groupClasses[$i]['class_name'];
        $next = ($i < count($groupClasses) - 1) ? $groupClasses[$i+1]['class_name'] : 'Pass Out / Alumni';
        $progressionMap[$current] = $next;
    }
}
$suggestedNextClass = $progressionMap[$selectedClass] ?? 'Next Class';
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold text-white">Student Promotion</h1>
        <p class="text-green-100 mt-1">Manage academic progressions and pass-out records</p>
    </div>
    
    <!-- Tab Switcher -->
    <div class="flex bg-white/20 p-1 rounded-xl backdrop-blur-sm border border-white/30">
        <button onclick="switchMode('bulk')" id="btn-mode-bulk" class="px-6 py-2 rounded-lg text-sm font-bold transition-all bg-white text-primary shadow-lg border-2 border-transparent">
            <i class="fas fa-users mr-2"></i> Bulk Promotion
        </button>
        <button onclick="switchMode('single')" id="btn-mode-single" class="px-6 py-2 rounded-lg text-sm font-bold transition-all text-white hover:bg-white/10 hover:border-white/30 border-2 border-transparent">
            <i class="fas fa-user mr-2"></i> Single Student
        </button>
    </div>
</div>

<div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg flex items-start gap-3">
    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
    <p class="text-sm text-blue-800"><strong>Note:</strong> Students can be promoted or passed out even with pending dues. Fee records remain in the Fees section — search by name or GR No to collect or view history later.</p>
</div>

<div id="bulk-mode-container" class="mode-container">
    <div class="bg-white rounded-lg shadow-lg p-6">
    <div class="mb-6 flex flex-col md:flex-row gap-4 items-end border-b border-gray-100 pb-6">
        <div class="flex-1 w-full">
            <label class="block text-sm font-bold text-gray-700 mb-2">Select Class to Promote</label>
            <div class="relative">
                <select id="classSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary shadow-sm text-gray-700 font-medium">
                    <option value="">Choose a class...</option>
                    <?php
                    $classes = $db->getClassNames();
                    foreach ($classes as $c) {
                        $selected = ($selectedClass == $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>
        </div>
        
        <?php if ($selectedClass && !empty($students)): ?>
        <div class="w-full md:w-72">
            <label class="block text-sm font-bold text-indigo-600 mb-2 tracking-tight flex items-center gap-2">
                <i class="fas fa-magic text-indigo-500"></i> Quick Set All Decisions
            </label>
            <select onchange="bulkSetDecisions(this.value)" class="w-full px-4 py-3 border-2 border-indigo-100 bg-indigo-50 text-indigo-800 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-bold cursor-pointer hover:bg-indigo-100 transition-colors">
                <option value="">Choose action...</option>
                <option value="pass">✓ Mark All as Pass</option>
                <option value="passout">🎓 Mark All as Pass Out</option>
                <option value="fail">✗ Mark All as Fail</option>
                <option value="stay">― Mark All as Stay Same</option>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($selectedClass && !empty($students)): ?>
    <div id="promotionSection">
        <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <h2 class="text-xl font-bold text-gray-800 text-center md:text-left flex items-center gap-2">
                <i class="fas fa-user-graduate text-primary"></i> 
                Students in <span class="text-primary"><?php echo htmlspecialchars($selectedClass); ?></span>
            </h2>
            <button onclick="applyPromotions()" class="w-full md:w-auto bg-primary text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-all font-bold shadow-lg hover:shadow-xl flex items-center justify-center gap-2 transform active:scale-95">
                <i class="fas fa-check-circle"></i> Apply Changes
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="studentsGrid">
            <?php foreach ($students as $student):
                $outstanding = $db->getStudentTotalOutstandingFees($student['gr_no']);
                $feesCleared = $outstanding < 0.01;
            ?>
            <div class="border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all bg-white group" data-student-id="<?php echo $student['id']; ?>">
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative">
                        <?php if (!empty($student['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="w-14 h-14 rounded-full object-cover shadow-sm bg-gray-50 border-2 border-white">
                        <?php else: ?>
                            <div class="w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold border-2 border-white shadow-sm">
                                <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-800 text-lg truncate group-hover:text-primary transition-colors"><?php echo htmlspecialchars($student['student_name']); ?></h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">GR: <?php echo htmlspecialchars($student['gr_no']); ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs mb-4 bg-gray-50 p-2 rounded-lg text-gray-600 border border-gray-100">
                    <div><span class="block font-bold text-[10px] text-gray-400 uppercase">Father</span> <?php echo htmlspecialchars($student['father_name']); ?></div>
                    <div><span class="block font-bold text-[10px] text-gray-400 uppercase">Current Class</span> <?php echo htmlspecialchars($student['current_class']); ?></div>
                </div>

                <?php if ($feesCleared): ?>
                <div class="mb-3 p-2 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700 font-bold flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Fees Cleared
                </div>
                <?php else: ?>
                <div class="mb-3 p-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 font-bold">
                    <div class="flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Fees Pending: Rs. <?php echo number_format($outstanding); ?></div>
                    <div class="text-[10px] font-medium text-amber-600 mt-1">Can collect later from Fees page</div>
                </div>
                <?php endif; ?>

                <div class="border-t pt-3">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Decision</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 rounded-lg hover:bg-emerald-50 cursor-pointer hover:border-emerald-100 transition-colors border border-transparent group/option">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="pass" class="mr-3 text-emerald-600 focus:ring-emerald-500" checked>
                            <span class="text-sm font-bold text-gray-600 group-hover/option:text-emerald-700">Promote to <?php echo htmlspecialchars($suggestedNextClass); ?></span>
                        </label>
                        <label class="flex items-center p-2 rounded-lg hover:bg-amber-50 cursor-pointer hover:border-amber-100 transition-colors border border-transparent group/option">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="passout" class="mr-3 text-amber-600 focus:ring-amber-500">
                            <span class="text-sm font-bold text-gray-600 group-hover/option:text-amber-700">Pass Out (Alumni)</span>
                        </label>
                        <label class="flex items-center p-2 rounded-lg hover:bg-red-50 cursor-pointer transition-colors border border-transparent hover:border-red-100 group/option">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="fail" class="mr-3 text-red-600 focus:ring-red-500">
                            <span class="text-sm font-bold text-gray-600 group-hover/option:text-red-700">Fail (Repeater)</span>
                        </label>
                        <label class="flex items-center p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors border border-transparent hover:border-gray-200 group/option">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="stay" class="mr-3 text-gray-500 focus:ring-gray-400">
                            <span class="text-sm font-bold text-gray-600 group-hover/option:text-gray-800">Stay Same</span>
                        </label>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($selectedClass): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-yellow-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-bold text-yellow-800">No Students Found</h3>
                <p class="text-sm text-yellow-700 mt-1">
                    There are currently no active students enrolled in <strong><?php echo htmlspecialchars($selectedClass); ?></strong>.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</div> 
<!-- End of Bulk Mode Container -->

<!-- Single Promotion Mode Container -->
<div id="single-mode-container" class="mode-container hidden">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="max-w-2xl mx-auto text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Single Student Promotion</h2>
            <p class="text-gray-500">Find and promote a student instantly.</p>
        </div>

        <div class="max-w-xl mx-auto mb-10">
            <!-- Search Type Selection -->
            <div class="flex justify-center gap-6 mb-4">
                <label class="flex items-center cursor-pointer gap-2">
                    <input type="radio" name="search_type" value="mix" checked class="text-primary focus:ring-primary" onchange="updateSearchPlaceholder()">
                    <span class="text-sm font-bold text-gray-700">Name or GR No.</span>
                </label>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-lg"></i>
                </div>
                <input type="text" id="single-search" placeholder="Enter Student Name or GR Number to search..." class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all text-lg shadow-sm">
                <div id="search-loading" class="hidden absolute right-4 top-4 text-indigo-600 animate-pulse font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i> Searching...
                </div>
            </div>
        </div>

        <div id="single-promotion-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <i class="fas fa-user-graduate text-4xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-400">Ready to Promote</h3>
                <p class="text-gray-400 text-sm max-w-xs mx-auto mt-1">Search results will appear here.</p>
            </div>
        </div>
    </div>
</div>

<script>
function switchMode(mode) {
    // Hide all
    document.getElementById('bulk-mode-container').classList.add('hidden');
    document.getElementById('single-mode-container').classList.add('hidden');
    document.getElementById('bulk-mode-container').style.display = 'none';
    document.getElementById('single-mode-container').style.display = 'none';

    // Show selected
    const selected = document.getElementById(mode + '-mode-container');
    selected.classList.remove('hidden');
    selected.style.display = 'block';
    
    // Update Buttons
    const btnBulk = document.getElementById('btn-mode-bulk');
    const btnSingle = document.getElementById('btn-mode-single');
    
    if (mode === 'bulk') {
        btnBulk.className = 'px-6 py-2 rounded-lg text-sm font-bold transition-all bg-white text-primary shadow-lg border-2 border-transparent transform scale-105';
        btnBulk.innerHTML = '<i class="fas fa-users mr-2"></i> Bulk Promotion';
        
        btnSingle.className = 'px-6 py-2 rounded-lg text-sm font-bold transition-all text-white hover:bg-white/10 hover:border-white/30 border-2 border-transparent opacity-80 hover:opacity-100';
    } else {
        btnSingle.className = 'px-6 py-2 rounded-lg text-sm font-bold transition-all bg-white text-primary shadow-lg border-2 border-transparent transform scale-105';
        
        btnBulk.className = 'px-6 py-2 rounded-lg text-sm font-bold transition-all text-white hover:bg-white/10 hover:border-white/30 border-2 border-transparent opacity-80 hover:opacity-100';
    }
}

function updateSearchPlaceholder() {
    // Currently only one option, but keeping for future or to avoid JS errors
    const searchInput = document.getElementById('single-search');
    searchInput.placeholder = "Enter Student Name or GR Number to search...";
}

function bulkSetDecisions(value) {
    if (!value) return;
    const radios = document.querySelectorAll(`input[value="${value}"]`);
    radios.forEach(radio => radio.checked = true);
    
    // visual feedback
    showModal('success', 'Selections Updated', `All visible students have been set to "${value.toUpperCase()}"`);
}

// Single Promotion Search
let searchTimeout;
const searchInput = document.getElementById('single-search');
const resultsContainer = document.getElementById('single-promotion-results');
const searchLoading = document.getElementById('search-loading');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
        if (query.length === 0) {
            resultsContainer.innerHTML = '<div class="col-span-full border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center text-gray-400"><i class="fas fa-search text-5xl mb-4 opacity-20"></i><p>Enter student details above to start individual promotion</p></div>';
        }
        return;
    }

    searchLoading.classList.remove('hidden');
    searchTimeout = setTimeout(() => {
        fetch(`../api/search_student_promotion.php?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                searchLoading.classList.add('hidden');
                
                if (data.error) {
                    resultsContainer.innerHTML = `<div class="col-span-full py-12 text-center text-red-500 italic"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><br>Error: ${data.error}</div>`;
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    resultsContainer.innerHTML = '<div class="col-span-full py-12 text-center text-gray-500 italic"><i class="fas fa-exclamation-circle text-2xl mb-2"></i><br>No matching active students found</div>';
                    return;
                }
                
                let html = '';
                data.forEach(student => {
                    const initial = student.student_name ? student.student_name[0].toUpperCase() : '?';
                    const feesCleared = student.fees_cleared;
                    const outstanding = Number(student.outstanding_fees || 0);
                    const feeBadge = feesCleared
                        ? `<div class="mb-3 p-2 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700 font-bold"><i class="fas fa-check-circle mr-1"></i> Fees Cleared</div>`
                        : `<div class="mb-3 p-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Fees Pending: Rs. ${outstanding.toLocaleString()}<div class="text-[10px] font-medium text-amber-600 mt-1">Can collect later from Fees page</div></div>`;

                    html += `
                    <div class="border border-indigo-100 rounded-xl p-5 hover:shadow-xl transition-all bg-white group" data-student-id="${student.id}">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="relative">
                                ${student.profile_image ? `<img src="${student.profile_image}" class="w-14 h-14 rounded-full object-cover shadow-sm bg-gray-50">` : `<div class="w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold border border-indigo-100">${initial}</div>`}
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 ${feesCleared ? 'bg-green-500' : 'bg-amber-500'} border-2 border-white rounded-full"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 group-hover:text-primary transition-colors truncate">${student.student_name}</h3>
                                <p class="text-xs font-bold text-gray-400">GR NO: ${student.gr_no}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 text-[11px] mb-4 bg-gray-50 p-2 rounded-lg text-gray-600">
                             <div><span class="opacity-60 block uppercase text-[9px] font-bold">Father</span> ${student.father_name}</div>
                             <div><span class="opacity-60 block uppercase text-[9px] font-bold">Class</span> ${student.current_class}</div>
                        </div>

                        ${feeBadge}

                        <div class="border-t pt-4">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Promotion Decision</label>
                            <div class="space-y-3">
                                <label class="flex items-center p-2 rounded-lg hover:bg-emerald-50 cursor-pointer hover:border-emerald-100 transition-colors border border-transparent">
                                    <input type="radio" name="promotion_${student.id}" value="pass" class="mr-3 text-emerald-600 focus:ring-emerald-500" checked>
                                    <span class="text-sm font-semibold text-emerald-800">✓ Promote to Next</span>
                                </label>
                                <label class="flex items-center p-2 rounded-lg hover:bg-amber-50 cursor-pointer hover:border-amber-100 transition-colors border border-transparent">
                                    <input type="radio" name="promotion_${student.id}" value="passout" class="mr-3 text-amber-600 focus:ring-amber-500">
                                    <span class="text-sm font-bold text-amber-700">🎓 Mark as Pass Out</span>
                                </label>
                                <label class="flex items-center p-2 rounded-lg hover:bg-red-50 cursor-pointer transition-colors border border-transparent hover:border-red-100">
                                    <input type="radio" name="promotion_${student.id}" value="fail" class="mr-3 text-red-600 focus:ring-red-500">
                                    <span class="text-sm font-semibold text-red-800">✗ Mark as Fail</span>
                                </label>
                            </div>

                            <button onclick="applySinglePromotion(${student.id}, this)" class="mt-5 w-full bg-indigo-600 text-white py-2 rounded-lg font-bold shadow-md hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-paper-plane text-xs"></i> Apply Decision
                            </button>
                        </div>
                    </div>`;
                });
                resultsContainer.innerHTML = html;
            })
            .catch(err => {
                searchLoading.classList.add('hidden');
                console.error(err);
                resultsContainer.innerHTML = '<div class="col-span-full py-12 text-center text-red-500 italic"><i class="fas fa-wifi text-2xl mb-2"></i><br>Network error or server unavailable</div>';
            });
    }, 400);
});

function applySinglePromotion(id, btn) {
    const radio = document.querySelector(`input[name="promotion_${id}"]:checked`);
    if (!radio) {
        showModal('warning', 'Missing Selection', 'Please choose a promotion decision first.');
        return;
    }

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('../api/promote_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: radio.value })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showModal('success', 'Success', 'Student status updated successfully!');
            // Remove the card with an animation
            const card = document.querySelector(`[data-student-id="${id}"]`);
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
            card.style.filter = 'grayscale(100%)';
            btn.innerHTML = '<i class="fas fa-check"></i> Applied';
            btn.className = "mt-5 w-full bg-gray-400 text-white py-2 rounded-lg font-bold cursor-not-allowed";
        } else {
            showModal('error', 'Error', data.error || 'Failed to apply promotion.');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    })
    .catch(err => {
        console.error(err);
        showModal('error', 'Network Error', 'Something went wrong. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}

document.getElementById('classSelect').addEventListener('change', function() {
    if (this.value) {
        window.location.href = 'promote_students.php?class=' + this.value;
    }
});

function applyPromotions(event) {
    const cards = document.querySelectorAll('#bulk-mode-container [data-student-id]');
    const promotions = [];
    
    cards.forEach(card => {
        const studentId = card.dataset.studentId;
        const selectedRadio = card.querySelector(`input[name="promotion_${studentId}"]:checked`);
        if (selectedRadio) {
            promotions.push({
                id: studentId,
                action: selectedRadio.value
            });
        }
    });

    if (promotions.length === 0) {
        showModal('warning', 'No Changes', 'Please select promotion decisions for students.');
        return;
    }

    if (!confirm(`Are you sure you want to apply these promotions to ${promotions.length} student(s)? This action cannot be undone.`)) {
        return;
    }

    // Disable button and show loading
    const applyBtn = document.querySelector('#bulk-mode-container button[onclick*="applyPromotions"]');
    const originalContent = applyBtn ? applyBtn.innerHTML : '';
    
    if (applyBtn) {
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
    }

    // Send single bulk request
    fetch('../api/bulk_promote_students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ promotions: promotions })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModal('success', 'Success', data.message || `Successfully processed ${promotions.length} student(s)!`);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showModal('error', 'Error', data.error || 'Failed to process promotions.');
            if (applyBtn) {
                applyBtn.disabled = false;
                applyBtn.innerHTML = originalContent;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModal('error', 'Network Error', 'Could not connect to the server. Please check your connection.');
        if (applyBtn) {
            applyBtn.disabled = false;
            applyBtn.innerHTML = originalContent;
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
