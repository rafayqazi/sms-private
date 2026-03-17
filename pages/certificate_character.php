<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();

// Initial search handling
$search = $_GET['search'] ?? '';
$allStudents = $db->readData();
$initialData = [];

if ($search) {
    $initialData = array_filter($allStudents, function($student) use ($search) {
        $term = strtolower($search);
        return (stripos($student['student_name'] ?? '', $term) !== false) || 
               (stripos($student['gr_no'] ?? '', $term) !== false);
    });
} else {
    // Default: Show some students (limited to 50 for performance)
    $initialData = array_slice($allStudents, 0, 50);
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <a href="certificates.php" class="text-gray-400 hover:text-purple-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-2xl text-purple-600 dark:text-purple-400 shadow-sm">
                    <i class="fas fa-user-check"></i>
                </div>
                Character Certificate
            </h2>
            <p class="text-slate-500 dark:text-gray-400 text-sm font-medium mt-1">Issue character certificates for students.</p>
        </div>
    </div>

    <!-- Search Section -->
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <!-- Search Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none p-8 border border-slate-100 dark:border-gray-700">
            <div class="max-w-3xl mx-auto">
                <label class="block text-xs font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-4 pl-1">Search Student Registration</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-300 group-focus-within:text-purple-500 transition-colors"></i>
                    </div>
                    <input type="text" id="studentSearch" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" 
                        placeholder="Type Student Name or GR Number..." 
                        class="w-full pl-14 pr-14 py-5 bg-slate-50 dark:bg-gray-900 border-2 border-transparent focus:border-purple-500 focus:bg-white dark:focus:bg-gray-950 rounded-2xl text-slate-800 dark:text-white font-bold transition-all shadow-sm outline-none">
                    
                    <div id="searchSpinner" class="absolute right-5 top-1/2 -translate-y-1/2 hidden">
                        <i class="fas fa-circle-notch fa-spin text-purple-500 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table Container -->
        <div id="results-container">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden border border-slate-100 dark:border-gray-700">
                <div id="table-container" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-gray-900 text-slate-400 dark:text-gray-500 text-[10px] uppercase font-black tracking-[0.2em]">
                                <th class="p-6 text-left">GR Number</th>
                                <th class="p-6 text-left">Student Information</th>
                                <th class="p-6 text-left">Father Name</th>
                                <th class="p-6 text-left">Current Class</th>
                                <th class="p-6 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsBody" class="divide-y divide-slate-50 dark:divide-gray-700">
                            <?php if (!empty($initialData)): ?>
                                <?php foreach ($initialData as $student): ?>
                                <tr class="hover:bg-purple-50/30 dark:hover:bg-purple-900/10 transition-colors">
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
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 uppercase tracking-tighter">
                                            <?php echo htmlspecialchars(($student['student_status'] === 'Alumni' ? 'Alumni ('.$student['last_class'].')' : $student['current_class']) ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <button onclick="openCharacterModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $student['id'],
                                            'caste' => $student['caste'] ?? '',
                                            'admission_class' => $student['admission_class'] ?? '',
                                            'admission_date' => $student['admission_date'] ?? '',
                                            'updated_at' => $student['updated_at'] ?? '',
                                            'student_status' => $student['student_status'] ?? 'Active',
                                            'last_class' => ($student['student_status'] === 'Alumni' ? $student['last_class'] : $student['current_class']) ?? ''
                                        ])); ?>)" 
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-purple-200 dark:shadow-none hover:scale-105 active:scale-95">
                                            <i class="fas fa-print"></i> Generate
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
</div>

<!-- Character Certificate Parameters Modal -->
<div id="characterParamsModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden animate-[scaleIn_0.3s_ease-out]">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-user-check text-purple-600"></i> Certificate Details
            </h3>
            <button onclick="closeCharacterModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-8 space-y-6">
            <input type="hidden" id="student_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Caste</label>
                    <input type="text" id="caste" placeholder="e.g. SINDHI" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-purple-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Study Duration (Years)</label>
                    <input type="text" id="years" placeholder="e.g. 2" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-purple-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Admission Class</label>
                    <input type="text" id="admission_class" placeholder="e.g. PRIMARY" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-purple-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Leaving Class</label>
                    <input type="text" id="leaving_class" placeholder="e.g. SECONDARY" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-purple-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Dated</label>
                    <input type="date" id="dated" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-purple-500 rounded-xl font-bold text-sm outline-none">
                </div>
            </div>
        </div>
        <div class="p-6 bg-slate-50 dark:bg-gray-800/50 flex gap-3">
            <button onclick="closeCharacterModal()" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-700 transition-colors">Cancel</button>
            <button onclick="proceedToPrint()" class="flex-[2] py-4 bg-purple-600 hover:bg-purple-700 text-white font-black rounded-2xl shadow-lg shadow-purple-200 dark:shadow-none transition-all active:scale-95">PROCEED TO PRINT</button>
        </div>
    </div>
</div>

<script>
    function openCharacterModal(student) {
        document.getElementById('student_id').value = student.id;
        document.getElementById('caste').value = student.caste || '';
        document.getElementById('admission_class').value = student.admission_class || '';
        document.getElementById('leaving_class').value = student.last_class || '';
        
        // Automated Duration Calculation
        let years = '';
        if (student.admission_date) {
            const startDate = new Date(student.admission_date);
            // If Alumni, use updated_at as leaving date, else use today
            const endDate = (student.student_status === 'Alumni' && student.updated_at) 
                ? new Date(student.updated_at) 
                : new Date();
            
            if (!isNaN(startDate.getTime())) {
                const diffTime = Math.abs(endDate - startDate);
                const diffYears = diffTime / (1000 * 60 * 60 * 24 * 365.25);
                years = diffYears.toFixed(1); // One decimal point e.g. 2.5
                if (years < 0.1) years = "0"; // Handle weird cases
                
                // If it's a whole number or very close, just show the integer
                if (Math.abs(Math.round(diffYears) - diffYears) < 0.05) {
                    years = Math.round(diffYears);
                }
            }
        }
        document.getElementById('years').value = years;
        
        const modal = document.getElementById('characterParamsModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeCharacterModal() {
        const modal = document.getElementById('characterParamsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function proceedToPrint() {
        const id = document.getElementById('student_id').value;
        const caste = document.getElementById('caste').value;
        const years = document.getElementById('years').value;
        const admission_class = document.getElementById('admission_class').value;
        const leaving_class = document.getElementById('leaving_class').value;
        const dated = document.getElementById('dated').value;

        let url = `print_character_certificate.php?id=${id}`;
        url += `&caste=${encodeURIComponent(caste)}`;
        url += `&years=${encodeURIComponent(years)}`;
        url += `&admission_class=${encodeURIComponent(admission_class)}`;
        url += `&leaving_class=${encodeURIComponent(leaving_class)}`;
        url += `&dated=${encodeURIComponent(dated)}`;

        window.open(url, '_blank');
        closeCharacterModal();
    }

    // Search logic
    const searchInput = document.getElementById('studentSearch');
    const searchSpinner = document.getElementById('searchSpinner');
    const searchResultsBody = document.getElementById('searchResultsBody');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 1) {
            return;
        }

        searchSpinner.classList.remove('hidden');

        debounceTimer = setTimeout(() => {
            fetch(`../api/search_students.php?query=${encodeURIComponent(query)}&include_alumni=1`)
                .then(response => response.json())
                .then(data => {
                    searchResultsBody.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-purple-50/30 dark:hover:bg-purple-900/10 transition-colors';
                            
                            // Handling complex data string from search_students.php
                            // Note: search_students.php usually returns data in a specific format
                            // I'll assume we need to fetch full student data or 
                            // we can use what's returned if it's rich enough.
                            
                            tr.innerHTML = `
                                <td class="p-6">
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-gray-700 rounded-lg text-slate-700 dark:text-white font-bold text-xs uppercase">${item.gr_no}</span>
                                </td>
                                <td class="p-6">
                                    <div class="font-bold text-slate-900 dark:text-white capitalize">${item.value}</div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">ID: ${item.id}</div>
                                </td>
                                <td class="p-6 font-medium text-slate-600 dark:text-gray-300 capitalize">
                                    ${item.father_name || 'N/A'}
                                </td>
                                <td class="p-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 uppercase tracking-tighter">
                                        ${item.current_class || 'N/A'}
                                    </span>
                                </td>
                                <td class="p-6 text-center">
                                    <button onclick="openCharacterModal(${JSON.stringify({
                                        id: item.id,
                                        caste: item.caste || '',
                                        admission_class: item.admission_class || '',
                                        admission_date: item.admission_date || '',
                                        updated_at: item.updated_at || '',
                                        student_status: item.student_status || 'Active',
                                        last_class: item.current_class || ''
                                    }).replace(/"/g, '&quot;')})" 
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-purple-200 dark:shadow-none hover:scale-105 active:scale-95">
                                        <i class="fas fa-print"></i> Generate
                                    </button>
                                </td>
                            `;
                            searchResultsBody.appendChild(tr);
                        });
                    }
                })
                .finally(() => {
                    searchSpinner.classList.add('hidden');
                });
        }, 300);
    });
</script>

<?php include '../includes/footer.php'; ?>
