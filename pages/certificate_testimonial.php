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
        return ((stripos($student['student_name'] ?? '', $term) !== false) || 
               (stripos($student['gr_no'] ?? '', $term) !== false));
    });
} else {
    // Default: Show only Alumni students (limited to 50 for performance)
    $alumniStudents = array_filter($allStudents, function($student) {
        return isset($student['student_status']) && strcasecmp($student['student_status'], 'Alumni') === 0;
    });
    $initialData = array_slice($alumniStudents, 0, 50);
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <a href="certificates.php" class="text-gray-400 hover:text-amber-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-2xl text-amber-600 dark:text-amber-400 shadow-sm">
                    <i class="fas fa-award"></i>
                </div>
                Testimonial Certificate
            </h2>
            <p class="text-slate-500 dark:text-gray-400 text-sm font-medium mt-1">Issue testimonial certificates for academic achievements.</p>
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
                        <i class="fas fa-search text-slate-300 group-focus-within:text-amber-500 transition-colors"></i>
                    </div>
                    <input type="text" id="studentSearch" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" 
                        placeholder="Type Student Name or GR Number..." 
                        class="w-full pl-14 pr-14 py-5 bg-slate-50 dark:bg-gray-900 border-2 border-transparent focus:border-amber-500 focus:bg-white dark:focus:bg-gray-950 rounded-2xl text-slate-800 dark:text-white font-bold transition-all shadow-sm outline-none">
                    
                    <div id="searchSpinner" class="absolute right-5 top-1/2 -translate-y-1/2 hidden">
                        <i class="fas fa-circle-notch fa-spin text-amber-500 text-lg"></i>
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
                                <tr class="hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
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
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 uppercase tracking-tighter">
                                            <?php echo htmlspecialchars(($student['student_status'] === 'Alumni' ? 'Alumni ('.$student['last_class'].')' : $student['current_class']) ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                        <button onclick="openTestimonialModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $student['id'],
                                            'gr_no' => $student['gr_no'],
                                            'date_of_birth' => $student['date_of_birth'] ?? '',
                                            'admission_date' => $student['admission_date'] ?? '',
                                            'updated_at' => $student['updated_at'] ?? '',
                                            'student_status' => $student['student_status'] ?? 'Active'
                                        ])); ?>)" 
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-amber-200 dark:shadow-none hover:scale-105 active:scale-95">
                                            <i class="fas fa-print"></i> Generate
                                        </button>
                                        <button onclick="openTestimonialModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $student['id'],
                                            'gr_no' => $student['gr_no'],
                                            'date_of_birth' => $student['date_of_birth'] ?? '',
                                            'admission_date' => $student['admission_date'] ?? '',
                                            'updated_at' => $student['updated_at'] ?? '',
                                            'student_status' => $student['student_status'] ?? 'Active'
                                        ])); ?>, 'pad')" 
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-emerald-200 dark:shadow-none hover:scale-105 active:scale-95">
                                            <i class="fas fa-print"></i> Generate (PAD)
                                        </button>
                                        </div>
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

<!-- Testimonial Certificate Parameters Modal -->
<div id="testimonialParamsModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden animate-[scaleIn_0.3s_ease-out] my-8">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-900 z-10">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-award text-amber-600"></i> Certificate Details
            </h3>
            <button onclick="closeTestimonialModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-8 space-y-6">
            <input type="hidden" id="student_id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">GR Number</label>
                    <input type="text" id="gr_no" placeholder="G.R No" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Date of Birth</label>
                    <input type="date" id="dob" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Study Duration (Years)</label>
                    <input type="text" id="years" placeholder="e.g. 2" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Exam Type</label>
                    <select id="exam_type" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none cursor-pointer">
                        <option value="SSC II">SSC II</option>
                        <option value="HSC II">HSC II</option>
                        <option value="SSC I">SSC I</option>
                        <option value="HSC I">HSC I</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Exam Category</label>
                    <select id="exam_category" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none cursor-pointer">
                        <option value="Annual">Annual / Annual</option>
                        <option value="Supp:">Supp: / Supplementary</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Exam Year</label>
                    <input type="text" id="exam_year" placeholder="e.g. 2023" value="<?php echo date('Y'); ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Board Name</label>
                    <input type="text" id="board_name" value="Hyderabad Sindh" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Seat No</label>
                    <input type="text" id="seat_no" placeholder="Seat No" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Candidate Type</label>
                    <select id="candidate_type" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none cursor-pointer">
                        <option value="Regular Candidate">Regular</option>
                        <option value="Private Candidate">Private</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Group</label>
                    <input type="text" id="group_name" placeholder="Science, Pre Engineering etc." value="Science Group" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Grade</label>
                    <input type="text" id="grade" placeholder="e.g. A-1" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">DOB in Words</label>
                    <input type="text" id="dob_words" placeholder="(Leave empty to auto-generate)" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-1">Dated</label>
                    <input type="date" id="dated" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-800 border-2 border-transparent focus:border-amber-500 rounded-xl font-bold text-sm outline-none">
                </div>
            </div>
        </div>
        <div class="p-6 bg-slate-50 dark:bg-gray-800/50 flex gap-3 sticky bottom-0 z-10">
            <button onclick="closeTestimonialModal()" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-700 transition-colors">Cancel</button>
            <button onclick="proceedToPrint()" class="flex-[2] py-4 bg-amber-600 hover:bg-amber-700 text-white font-black rounded-2xl shadow-lg shadow-amber-200 dark:shadow-none transition-all active:scale-95">PROCEED TO PRINT</button>
        </div>
    </div>
</div>

<script>
    let currentTestimonialFormat = null;

    function openTestimonialModal(student, format = null) {
        currentTestimonialFormat = format;
        document.getElementById('student_id').value = student.id;
        document.getElementById('gr_no').value = student.gr_no || '';
        document.getElementById('dob').value = student.date_of_birth || '';
        
        let years = '';
        if (student.admission_date) {
            const startDate = new Date(student.admission_date);
            const endDate = (student.student_status === 'Alumni' && student.updated_at) 
                ? new Date(student.updated_at) 
                : new Date();
            
            if (!isNaN(startDate.getTime())) {
                const diffTime = Math.abs(endDate - startDate);
                const diffYears = diffTime / (1000 * 60 * 60 * 24 * 365.25);
                years = diffYears.toFixed(1);
                if (years < 0.1) years = "0";
                if (Math.abs(Math.round(diffYears) - diffYears) < 0.05) {
                    years = Math.round(diffYears);
                }
            }
        }
        document.getElementById('years').value = years;
        document.getElementById('dob_words').value = '';
        document.getElementById('seat_no').value = ''; 
        document.getElementById('grade').value = ''; 
        
        const modal = document.getElementById('testimonialParamsModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeTestimonialModal() {
        const modal = document.getElementById('testimonialParamsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function proceedToPrint() {
        const id = document.getElementById('student_id').value;
        const gr_no = document.getElementById('gr_no').value;
        const dob = document.getElementById('dob').value;
        const years = document.getElementById('years').value;
        const exam_type = document.getElementById('exam_type').value;
        const exam_category = document.getElementById('exam_category').value;
        const exam_year = document.getElementById('exam_year').value;
        const board_name = document.getElementById('board_name').value;
        const seat_no = document.getElementById('seat_no').value;
        const candidate_type = document.getElementById('candidate_type').value;
        const group_name = document.getElementById('group_name').value;
        const grade = document.getElementById('grade').value;
        const dob_words = document.getElementById('dob_words').value;
        const dated = document.getElementById('dated').value;

        const params = new URLSearchParams({
            id: id,
            gr_no: gr_no,
            dob: dob,
            years: years,
            exam_type: exam_type,
            exam_category: exam_category,
            exam_year: exam_year,
            board_name: board_name,
            seat_no: seat_no,
            candidate_type: candidate_type,
            group_name: group_name,
            grade: grade,
            dob_words: dob_words,
            dated: dated
        });

        const basePage = currentTestimonialFormat === 'pad' ? 'print_testimonial_certificate_pad.php' : 'print_testimonial_certificate.php';
        window.open(basePage + '?' + params.toString(), '_blank');
        closeTestimonialModal();
    }

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
            // Include a param to specifically search ONLY alumni for testimonials
            fetch(`../api/search_students.php?query=${encodeURIComponent(query)}&include_alumni=1&alumni_only=1`)
                .then(response => response.json())
                .then(data => {
                    searchResultsBody.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors';
                            
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
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 uppercase tracking-tighter">
                                        ${item.current_class || 'N/A'}
                                    </span>
                                </td>
                                <td class="p-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                    <button onclick="openTestimonialModal(${JSON.stringify({
                                        id: item.id,
                                        gr_no: item.gr_no,
                                        date_of_birth: item.date_of_birth || '',
                                        admission_date: item.admission_date || '',
                                        updated_at: item.updated_at || '',
                                        student_status: item.student_status || 'Active'
                                    }).replace(/"/g, '&quot;')})" 
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-amber-200 dark:shadow-none hover:scale-105 active:scale-95">
                                        <i class="fas fa-print"></i> Generate
                                    </button>
                                    <button onclick="openTestimonialModal(${JSON.stringify({
                                        id: item.id,
                                        gr_no: item.gr_no,
                                        date_of_birth: item.date_of_birth || '',
                                        admission_date: item.admission_date || '',
                                        updated_at: item.updated_at || '',
                                        student_status: item.student_status || 'Active'
                                    }).replace(/"/g, '&quot;')}, 'pad')" 
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-emerald-200 dark:shadow-none hover:scale-105 active:scale-95">
                                        <i class="fas fa-print"></i> Generate (PAD)
                                    </button>
                                    </div>
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
