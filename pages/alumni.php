<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('alumni.php')) {
    header("Location: ../index.php");
    exit;
}
$db = new Database();

// Get filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$yearFilter = isset($_GET['year']) ? $_GET['year'] : '';

// Get all alumni students
$allStudents = $db->readData();
$alumniStudents = array_filter($allStudents, function($student) use ($search, $yearFilter) {
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    if (!$isAlumni) return false;

    // Filter by year
    if ($yearFilter) {
        $gradYear = isset($student['graduation_year']) ? $student['graduation_year'] : 
                    (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : '');
        if ($gradYear !== $yearFilter) return false;
    }

    // Filter by search keyword
    if ($search) {
        $nameMatch = stripos($student['student_name'] ?? '', $search) !== false;
        $grMatch = stripos($student['gr_no'] ?? '', $search) !== false;
        if (!$nameMatch && !$grMatch) return false;
    }

    return true;
});

// Sort by Graduation Year (desc) then GR number (asc)
usort($alumniStudents, function($a, $b) {
    $yearA = $a['graduation_year'] ?? (isset($a['updated_at']) ? date('Y', strtotime($a['updated_at'])) : '0');
    $yearB = $b['graduation_year'] ?? (isset($b['updated_at']) ? date('Y', strtotime($b['updated_at'])) : '0');
    
    if ($yearA != $yearB) return (int)$yearB - (int)$yearA;
    return (int)$a['gr_no'] - (int)$b['gr_no'];
});

// Get unique years for the filter dropdown
$years = array_unique(array_map(function($s) {
    return $s['graduation_year'] ?? (isset($s['updated_at']) ? date('Y', strtotime($s['updated_at'])) : 'Unknown');
}, array_filter($allStudents, fn($s) => ($s['student_status'] ?? '') === 'Alumni')));
rsort($years);
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">Alumni Network</h1>
        <p class="text-green-100 mt-1">Former students of <?php echo htmlspecialchars($headerSettings['school_name'] ?? 'School Name'); ?></p>
    </div>
    <div class="text-center md:text-right w-full md:w-auto p-3 bg-white/10 rounded-lg backdrop-blur-sm border border-white/20">
        <div class="text-4xl font-bold"><?php echo count($alumniStudents); ?></div>
        <div class="text-sm text-green-100 uppercase tracking-wider font-semibold">Total Records</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-100">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Alumnus</label>
            <div class="relative">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter Name or GR No..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Graduation Year</label>
            <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                <option value="">All Years</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $yearFilter === $y ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <button type="submit" class="flex-1 md:flex-none bg-primary text-white px-6 py-2 rounded-md hover:bg-accent transition-colors font-semibold">
                Filter
            </button>
            <?php if ($search || $yearFilter): ?>
                <a href="alumni.php" class="flex-1 md:flex-none bg-gray-100 text-gray-600 px-6 py-2 rounded-md hover:bg-gray-200 transition-colors font-semibold text-center">
                    Clear
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
    <?php if (empty($alumniStudents)): ?>
    <div class="p-12 text-center">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-graduate text-gray-300 text-4xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">No Alumni Found</h3>
        <p class="text-gray-500 max-w-xs mx-auto">We couldn't find any alumni records matching your current filters.</p>
        <?php if ($search || $yearFilter): ?>
            <a href="alumni.php" class="mt-4 inline-block text-primary font-semibold hover:underline">Show all alumni</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-bold">
                    <th class="p-4 w-12 text-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                    </th>
                    <th class="p-4 w-16 text-center">S#</th>
                    <th class="p-4">GR No</th>
                    <th class="p-4">Student Information</th>
                    <th class="p-4 text-center">Last Class</th>
                    <th class="p-4 text-center">Graduated</th>
                    <th class="p-4">Admission Date</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $i = 1; foreach ($alumniStudents as $student): 
                    $graduationYear = $student['graduation_year'] ?? (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : 'Unknown');
                    $lastClass = !empty($student['last_class']) && $student['last_class'] !== 'N/A' ? $student['last_class'] : 'N/A';
                    
                    // Fallback for older records where last_class wasn't set
                    if ($lastClass === 'N/A' && isset($student['current_class'])) {
                        // If current_class is not simply "Alumni", it likely contains the last class name
                        if (stripos($student['current_class'], 'Alumni') === false) {
                            $lastClass = $student['current_class'];
                        }
                    }
                ?>
                <tr class="hover:bg-gray-50/50 transition-colors student-row" data-id="<?php echo $student['id']; ?>">
                    <td class="p-4 text-center">
                        <input type="checkbox" class="student-checkbox w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" value="<?php echo $student['id']; ?>">
                    </td>
                    <td class="p-4 text-gray-400 font-medium text-sm"><?php echo $i++; ?></td>
                    <td class="p-4 text-gray-700 font-bold"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                    <td class="p-4 text-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <?php if (!empty($student['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="w-10 h-10 rounded-lg object-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold border border-emerald-100">
                                        <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 border-2 border-white rounded-full" title="Alumni Status"></div>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 capitalize"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                <div class="text-[10px] text-gray-500 font-medium flex items-center gap-1">
                                    <i class="fas fa-user-friends"></i> F: <?php echo htmlspecialchars($student['father_name']); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-center">
                        <span class="px-2 py-1 rounded bg-blue-50 text-blue-600 text-[10px] font-bold border border-blue-100">
                           Class: <?php echo htmlspecialchars($lastClass); ?>
                        </span>
                    </td>
                    <td class="p-4 text-center">
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-600 text-white shadow-sm inline-flex items-center gap-1">
                            <i class="fas fa-calendar-alt text-[9px]"></i> <?php echo $graduationYear; ?>
                        </span>
                    </td>
                    <td class="p-4 text-gray-500 text-xs italic font-medium"><?php echo htmlspecialchars($student['admission_date']); ?></td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="restoreSingle(<?php echo $student['id']; ?>, '<?php echo addslashes($lastClass); ?>')" class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Quick Restore to <?php echo htmlspecialchars($lastClass); ?>">
                                <i class="fas fa-undo-alt text-xs"></i>
                            </button>
                            <a href="student_form.php?id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center hover:bg-yellow-600 hover:text-white transition-all shadow-sm" title="Edit Profile">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Full Profile">
                                <i class="fas fa-user-graduate text-xs"></i>
                            </a>
                            <a href="generate_id_card.php?id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all shadow-sm" title="Print Certificate Placeholder (View ID)">
                                <i class="fas fa-certificate text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-indigo-600 rounded-xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <i class="fas fa-graduation-cap text-9xl"></i>
        </div>
        <h4 class="font-bold mb-2">Class Reunions</h4>
        <p class="text-indigo-100 text-sm mb-4">You can now filter alumni by their graduation year to organize class reunions or track cohort progress.</p>
        <div class="text-[10px] font-bold uppercase tracking-widest opacity-70">Best Practice Implementation</div>
    </div>
    
    <div class="bg-emerald-600 rounded-xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <i class="fas fa-search-location text-9xl"></i>
        </div>
        <h4 class="font-bold mb-2">Detailed Tracking</h4>
        <p class="text-emerald-100 text-sm mb-4">We now preserve the "Last Class" attended, even if the student left before completing Class 5.</p>
        <div class="text-[10px] font-bold uppercase tracking-widest opacity-70">Data Integrity</div>
    </div>

    <div class="bg-amber-600 rounded-xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <i class="fas fa-chart-line text-9xl"></i>
        </div>
        <h4 class="font-bold mb-2">Future Ready</h4>
        <p class="text-amber-100 text-sm mb-4">This centralized database is ready for integration with career tracking or alumni messaging systems.</p>
        <div class="text-[10px] font-bold uppercase tracking-widest opacity-70">Scalable Architecture</div>
    </div>
</div>

<!-- Bulk Action Bar -->
<div id="bulkActionBar" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-slate-900/90 backdrop-blur-md text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-6 border border-white/10 transition-all duration-300 translate-y-32 z-50">
    <div class="flex items-center gap-3 pr-6 border-r border-white/20">
        <div class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center text-primary">
            <i class="fas fa-users-cog"></i>
        </div>
        <div>
            <div class="font-bold text-sm leading-tight"><span id="selectedCount">0</span> Selected</div>
            <div class="text-[10px] text-gray-400 uppercase tracking-widest">Alumni Students</div>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="openRestoreModal()" class="flex items-center gap-2 bg-primary hover:bg-green-600 px-5 py-2.5 rounded-lg font-bold text-sm transition-all shadow-lg active:scale-95">
            <i class="fas fa-undo"></i> Bulk Restore to Class
        </button>
        <button onclick="deselectAll()" class="text-gray-400 hover:text-white text-sm font-medium transition-colors">
            Deselect All
        </button>
    </div>
</div>

<!-- Bulk Restore Modal -->
<div id="restoreModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transition-all transform scale-95 opacity-0" id="restoreModalContent">
        <div class="bg-primary p-6 text-white text-center pb-8">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/30">
                <i class="fas fa-user-graduate text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold">Restore Alumni</h3>
            <p class="text-green-100 text-sm opacity-80 mt-1">Select the target class to reactivate these students</p>
        </div>
        
        <div class="p-6 bg-gray-50">
            <label class="block text-sm font-bold text-gray-700 mb-2">Target Restore Class</label>
            <div class="relative">
                <select id="restoreClassSelect" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all text-gray-700 font-semibold shadow-sm">
                    <option value="">Select Class...</option>
                    <?php
                    $allClasses = $db->getClassNames();
                    foreach ($allClasses as $c) {
                        echo "<option value=\"$c\">$c</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="mt-8 flex gap-3">
                <button onclick="closeRestoreModal()" class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-100 transition-colors shadow-sm">
                    Cancel
                </button>
                <button onclick="executeBulkRestore()" id="btnExecuteRestore" class="flex-1 px-4 py-3 bg-primary text-white font-bold rounded-xl hover:bg-green-700 transition-all shadow-lg flex items-center justify-center gap-2 transform active:scale-95">
                    <i class="fas fa-check-circle"></i> Restore Now
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.student-checkbox');
const bulkBar = document.getElementById('bulkActionBar');
const countLabel = document.getElementById('selectedCount');

function updateBulkUI() {
    const selected = document.querySelectorAll('.student-checkbox:checked');
    const count = selected.length;
    
    if (count > 0) {
        countLabel.textContent = count;
        bulkBar.classList.remove('translate-y-32');
        bulkBar.classList.add('translate-y-0');
    } else {
        bulkBar.classList.remove('translate-y-0');
        bulkBar.classList.add('translate-y-32');
    }
}

selectAll.addEventListener('change', function() {
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
        const row = cb.closest('.student-row');
        if (selectAll.checked) row.classList.add('bg-indigo-50/50');
        else row.classList.remove('bg-indigo-50/50');
    });
    updateBulkUI();
});

checkboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        const row = this.closest('.student-row');
        if (this.checked) row.classList.add('bg-indigo-50/50');
        else row.classList.remove('bg-indigo-50/50');
        
        // Update Select All state
        const allChecked = Array.from(checkboxes).every(c => c.checked);
        selectAll.checked = allChecked;
        
        updateBulkUI();
    });
});

function deselectAll() {
    selectAll.checked = false;
    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.closest('.student-row').classList.remove('bg-indigo-50/50');
    });
    updateBulkUI();
}

function openRestoreModal() {
    const modal = document.getElementById('restoreModal');
    const content = document.getElementById('restoreModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeRestoreModal() {
    const modal = document.getElementById('restoreModal');
    const content = document.getElementById('restoreModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function executeBulkRestore() {
    const targetClass = document.getElementById('restoreClassSelect').value;
    const selectedIds = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
    const btn = document.getElementById('btnExecuteRestore');

    if (!targetClass) {
        alert('Please select a target class to restore the students to.');
        return;
    }

    if (!confirm(`Are you sure you want to restore ${selectedIds.length} students to ${targetClass}?`)) {
        return;
    }

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';

    fetch('../api/bulk_restore_alumni.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            student_ids: selectedIds,
            target_class: targetClass
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.className = "flex-1 px-4 py-3 bg-emerald-600 text-white font-bold rounded-xl flex items-center justify-center gap-2";
            btn.innerHTML = '<i class="fas fa-check"></i> Success!';
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert(data.message || 'Operation failed.');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error occurred.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}

function restoreSingle(id, lastClass) {
    if (lastClass === 'N/A' || !lastClass) {
        alert('Last attended class is unknown for this student. Please use the Bulk Restore option to specify a class.');
        return;
    }

    if (!confirm(`Are you sure you want to restore this student back to ${lastClass}?`)) {
        return;
    }

    // Reuse the bulk restore API for single restore
    fetch('../api/bulk_restore_alumni.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            student_ids: [id],
            target_class: lastClass
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Operation failed.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error occurred.');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
