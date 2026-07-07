<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();

// Get unique years for the bulk filter dropdown (from Alumni only)
$allStudents = $db->readData();
$alumni = array_filter($allStudents, fn($s) => ($s['student_status'] ?? '') === 'Alumni');

$years = array_unique(array_map(function($s) {
    return $s['graduation_year'] ?? (isset($s['updated_at']) ? date('Y', strtotime($s['updated_at'])) : 'Unknown');
}, $alumni));
rsort($years);

// Initial search handling (for direct links or page reloads)
$search = $_GET['search'] ?? '';
$initialData = [];
if ($search) {
    $initialData = array_filter($alumni, function($student) use ($search) {
        $term = strtolower($search);
        return (stripos($student['student_name'] ?? '', $term) !== false) || 
               (stripos($student['gr_no'] ?? '', $term) !== false);
    });
} else {
    // Default: Show all alumni (limited to 50 for performance)
    $initialData = array_slice($alumni, 0, 50);
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <a href="certificates.php" class="text-gray-400 hover:text-indigo-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-2xl text-indigo-600 dark:text-indigo-400 shadow-sm">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                School Leaving Certificate
            </h2>
            <p class="text-slate-500 dark:text-gray-400 text-sm font-medium mt-1">Issue and manage certificates for alumni students.</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-8 bg-slate-100 dark:bg-gray-900 p-1.5 rounded-2xl w-fit">
        <button onclick="switchTab('single')" id="tab-single" class="px-8 py-3 rounded-xl font-bold transition-all flex items-center gap-2 bg-white dark:bg-gray-800 text-indigo-600 shadow-sm">
            <i class="fas fa-search"></i> Print Single
        </button>
        <button onclick="switchTab('bulk')" id="tab-bulk" class="px-8 py-3 rounded-xl font-bold transition-all flex items-center gap-2 text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-layer-group"></i> Print Bulk
        </button>
    </div>

    <!-- Single Print Section -->
    <div id="view-single" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <!-- Search Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none p-8 border border-slate-100 dark:border-gray-700">
            <div class="max-w-3xl mx-auto">
                <label class="block text-xs font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-4 pl-1">Search Alumni Registration</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                    </div>
                    <input type="text" id="studentSearch" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" 
                        placeholder="Type Student Name or GR Number..." 
                        class="w-full pl-14 pr-14 py-5 bg-slate-50 dark:bg-gray-900 border-2 border-transparent focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-950 rounded-2xl text-slate-800 dark:text-white font-bold transition-all shadow-sm outline-none">
                    
                    <div id="searchSpinner" class="absolute right-5 top-1/2 -translate-y-1/2 hidden">
                        <i class="fas fa-circle-notch fa-spin text-indigo-500 text-lg"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-widest pl-1">
                    <i class="fas fa-info-circle text-indigo-400"></i>
                    Results will update automatically as you type
                </div>
            </div>
        </div>

        <!-- Results Table Container -->
        <div id="results-container">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden border border-slate-100 dark:border-gray-700">
                <div class="p-6 border-b border-slate-50 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-900/50 flex justify-between items-center">
                    <h3 id="results-title" class="text-sm font-black text-slate-700 dark:text-gray-200 uppercase tracking-widest">
                        <?php echo $search ? 'Search Results ('.count($initialData).')' : 'Alumni Students ('.count($initialData).')'; ?>
                    </h3>
                </div>

                <div id="no-results" class="p-20 text-center <?php echo ($search && empty($initialData)) ? '' : 'hidden'; ?>">
                    <div class="w-20 h-20 bg-slate-50 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-user-slash text-3xl text-slate-300"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 dark:text-white mb-2">No Alumni Found</h4>
                    <p class="text-slate-500 dark:text-gray-400">We couldn't find any alumni matching "<span id="search-term-display" class="text-indigo-500 font-bold"><?php echo htmlspecialchars($search); ?></span>".</p>
                </div>

                <div id="table-container" class="overflow-x-auto <?php echo (empty($initialData) && $search) ? 'hidden' : ''; ?>">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-gray-900 text-slate-400 dark:text-gray-500 text-[10px] uppercase font-black tracking-[0.2em]">
                                <th class="p-6 text-left">GR Number</th>
                                <th class="p-6 text-left">Student Information</th>
                                <th class="p-6 text-left">Father Name</th>
                                <th class="p-6 text-left">Leaving Class</th>
                                <th class="p-6 text-left">Graduation</th>
                                <th class="p-6 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsBody" class="divide-y divide-slate-50 dark:divide-gray-700">
                            <?php if (!empty($initialData)): ?>
                                <?php foreach ($initialData as $student): ?>
                                <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="p-6">
                                        <span class="px-3 py-1 bg-slate-100 dark:bg-gray-700 rounded-lg text-slate-700 dark:text-white font-bold text-xs uppercase"><?php echo htmlspecialchars($student['gr_no']); ?></span>
                                    </td>
                                    <td class="p-6">
                                        <div class="font-bold text-slate-900 dark:text-white capitalize"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">ID: <?php echo $student['id']; ?></div>
                                    </td>
                                    <td class="p-6 font-medium text-slate-600 dark:text-gray-300 capitalize">
                                        <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="p-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase tracking-tighter">
                                            <?php echo htmlspecialchars($student['last_class'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="p-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-tighter">
                                            <?php echo htmlspecialchars($student['graduation_year'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <button onclick="promptLeavingDate({type: 'single', id: <?php echo $student['id']; ?>, class: '<?php echo addslashes($student['last_class'] ?? $student['current_class']); ?>'})"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:scale-105 active:scale-95">
                                            <i class="fas fa-print"></i> Print SLC
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Print Section -->
    <div id="view-bulk" class="hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none p-12 max-w-2xl mx-auto text-center border border-slate-100 dark:border-gray-700">
            <div class="w-24 h-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center mx-auto mb-8 text-indigo-600 dark:text-indigo-400">
                <i class="fas fa-layer-group text-4xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-4">Bulk Issue Certificates</h3>
            <p class="text-slate-500 dark:text-gray-400 mb-10 font-medium">Generate School Leaving Certificates for an entire batch. Select the graduation year below to proceed.</p>

            <form onsubmit="event.preventDefault(); if(this.year.value) promptLeavingDate({type: 'bulk', year: this.year.value})" class="max-w-md mx-auto space-y-6">
                <div class="text-left">
                    <label class="block text-xs font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-3 pl-1">Select Graduation Year</label>
                    <div class="relative">
                        <select name="year" required class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-900 border-2 border-transparent focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-950 rounded-2xl text-slate-800 dark:text-white font-bold transition-all outline-none appearance-none cursor-pointer">
                            <option value="">Select Year...</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo htmlspecialchars($y); ?>"><?php echo htmlspecialchars($y); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>
                <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white font-black py-5 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none transition-all flex items-center justify-center gap-3 active:scale-[0.98]">
                    <i class="fas fa-print"></i> 
                    <span class="uppercase tracking-[0.2em]">Generate Batch</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Leaving Date Modal -->
<div id="leavingDateModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-[scaleIn_0.3s_ease-out]">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center text-gray-800 dark:text-gray-100">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-calendar-alt text-indigo-500"></i> School Leaving Date
            </h3>
            <button onclick="closeLeavingDateModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-8">
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-2 pl-1">Date of Leaving the School</label>
                    <input type="date" id="leavingDateInput" value="<?php echo date('Y-m-d'); ?>" 
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-900 border-2 border-transparent focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-950 rounded-2xl text-slate-800 dark:text-white font-bold transition-all outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-2 pl-1">Class at the time of Leaving</label>
                    <input type="text" id="leavingClassInput" 
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-900 border-2 border-transparent focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-950 rounded-2xl text-slate-800 dark:text-white font-bold transition-all outline-none"
                        placeholder="e.g. Three">
                </div>
            </div>
            <button onclick="confirmLeavingDate()" class="w-full mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-indigo-200 dark:shadow-none transition-all flex items-center justify-center gap-3 active:scale-[0.98]">
                <i class="fas fa-print"></i> 
                <span class="uppercase tracking-[0.2em]">Confirm & Print</span>
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes scaleIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('studentSearch');
        const searchSpinner = document.getElementById('searchSpinner');
        const resultsContainer = document.getElementById('results-container');
        const resultsTitle = document.getElementById('results-title');
        const searchResultsBody = document.getElementById('searchResultsBody');
        const noResults = document.getElementById('no-results');
        const searchTermDisplay = document.getElementById('search-term-display');
        const tableContainer = document.getElementById('table-container');
        let debounceTimer;

        const initialResultsHTML = searchResultsBody.innerHTML;
        const initialResultsTitle = resultsTitle.textContent;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 1) {
                searchResultsBody.innerHTML = initialResultsHTML;
                resultsTitle.textContent = initialResultsTitle;
                noResults.classList.add('hidden');
                tableContainer.classList.remove('hidden');
                return;
            }

            searchSpinner.classList.remove('hidden');

            debounceTimer = setTimeout(() => {
                fetch(`../api/search_alumni.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResultsBody.innerHTML = '';
                        resultsContainer.classList.remove('hidden');
                        
                        if (data && data.length > 0) {
                            noResults.classList.add('hidden');
                            tableContainer.classList.remove('hidden');
                            resultsTitle.textContent = `Search Results (${data.length})`;
                            
                            data.forEach(item => {
                                const tr = document.createElement('tr');
                                tr.className = 'hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors';
                                tr.innerHTML = `
                                    <td class="p-6">
                                        <span class="px-3 py-1 bg-slate-100 dark:bg-gray-700 rounded-lg text-slate-700 dark:text-white font-bold text-xs uppercase">${item.gr_no}</span>
                                    </td>
                                    <td class="p-6">
                                        <div class="font-bold text-slate-900 dark:text-white capitalize">${item.value}</div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">ID: ${item.id}</div>
                                    </td>
                                    <td class="p-6 font-medium text-slate-600 dark:text-gray-300 capitalize">
                                        ${item.father_name}
                                    </td>
                                    <td class="p-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase tracking-tighter">
                                            ${item.last_class}
                                        </span>
                                    </td>
                                    <td class="p-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-tighter">
                                            ${item.graduation_year}
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <button onclick="promptLeavingDate({type: 'single', id: ${item.id}, class: '${item.last_class}'})"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:scale-105 active:scale-95">
                                            <i class="fas fa-print"></i> Print SLC
                                        </button>
                                    </td>
                                `;
                                searchResultsBody.appendChild(tr);
                            });
                        } else {
                            resultsTitle.textContent = 'Search Results (0)';
                            noResults.classList.remove('hidden');
                            tableContainer.classList.add('hidden');
                            searchTermDisplay.textContent = query;
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        // Show zero results on error
                        resultsTitle.textContent = 'Search Error';
                        noResults.classList.remove('hidden');
                        tableContainer.classList.add('hidden');
                        searchTermDisplay.textContent = 'unsupported query';
                    })
                    .finally(() => {
                        searchSpinner.classList.add('hidden');
                    });
            }, 300);
        });
    });

    function switchTab(tab) {
        const singleView = document.getElementById('view-single');
        const bulkView = document.getElementById('view-bulk');
        const tabSingle = document.getElementById('tab-single');
        const tabBulk = document.getElementById('tab-bulk');

        if (tab === 'single') {
            singleView.classList.remove('hidden');
            bulkView.classList.add('hidden');
            
            tabSingle.className = "px-8 py-3 rounded-xl font-bold transition-all flex items-center gap-2 bg-white dark:bg-gray-800 text-indigo-600 shadow-sm";
            tabBulk.className = "px-8 py-3 rounded-xl font-bold transition-all flex items-center gap-2 text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200";
        } else {
            singleView.classList.add('hidden');
            bulkView.classList.remove('hidden');
            
            tabBulk.className = "px-8 py-3 rounded-xl font-bold transition-all flex items-center gap-2 bg-white dark:bg-gray-800 text-indigo-600 shadow-sm";
            tabSingle.className = "px-8 py-3 rounded-xl font-bold transition-all flex items-center gap-2 text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200";
        }
    }

    let currentPrintTarget = null;

    function promptLeavingDate(target) {
        currentPrintTarget = target;
        const classInput = document.getElementById('leavingClassInput');
        // Pre-fill class for single student, leave empty for bulk
        if (target.type === 'single' && target.class) {
            classInput.value = target.class;
        } else {
            classInput.value = '';
        }
        const modal = document.getElementById('leavingDateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLeavingDateModal() {
        const modal = document.getElementById('leavingDateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function confirmLeavingDate() {
        const dateInput = document.getElementById('leavingDateInput');
        const date = dateInput.value;
        const classInput = document.getElementById('leavingClassInput');
        const leavingClass = classInput.value.trim();
        
        if (!date) {
            alert('Please select a date.');
            return;
        }
        
        let url = 'print_school_leaving.php?leaving_date=' + date;
        if (leavingClass) {
            url += '&leaving_class=' + encodeURIComponent(leavingClass);
        }
        if (currentPrintTarget.type === 'single') {
            url += '&student_id=' + currentPrintTarget.id;
        } else {
            url += '&year=' + currentPrintTarget.year;
        }
        
        window.open(url, '_blank');
        closeLeavingDateModal();
    }
</script>

<?php include '../includes/footer.php'; ?>
