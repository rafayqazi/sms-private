<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$allowedClasses = getAssignedClasses();
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="../index.php" class="hover:text-indigo-600 transition-colors"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                <span>Examination</span>
                <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                <span class="text-gray-800 dark:text-gray-200 font-semibold">Marking Award List</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-6 md:p-8">
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100 dark:border-gray-800">
                <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-2xl shrink-0 shadow-inner">
                    <i class="fas fa-table-list"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-black text-gray-800 dark:text-gray-100 tracking-tight">
                        Generate Marking Award List
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Configure multiple subjects with custom max marks (e.g. 100, 75, 60) for a comprehensive class marking sheet.
                    </p>
                </div>
            </div>

            <form action="print_marking_award_list.php" method="GET" target="_blank" class="space-y-6" id="markingAwardListForm">
                <!-- Examination Name -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                        Examination Name <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-poll-h"></i>
                        </div>
                        <input type="text" name="exam_name" value="PRE – BOARD EXAMINATION 2025-26" placeholder="e.g. ANNUAL EXAMINATION 2026" required
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all text-sm font-medium">
                    </div>
                </div>

                <!-- Class & Starting Seat # Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Class Selection -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Class <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <select name="class" id="classSelect" required
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all text-sm font-medium">
                                <option value="">Select Class</option>
                                <?php foreach ($allowedClasses as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $selectedClass === $class ? 'selected' : ''; ?>>
                                        Class <?php echo htmlspecialchars($class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Starting Seat # -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Starting Seat # <span class="text-xs font-normal text-gray-400">(Optional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <input type="number" name="starting_seat_no" placeholder="e.g. 1 (Default: starts at 1, e.g. CLASS-1, CLASS-2...)" min="1"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all text-sm font-medium">
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Seat numbers match Exam Slips (e.g. CLASS-1, CLASS-2...) sorted Boys first, then Girls by GR#.
                        </p>
                    </div>
                </div>

                <!-- Subjects & Max Marks Section -->
                <div class="bg-gray-50 dark:bg-gray-800/50 p-4 md:p-5 rounded-2xl border border-gray-200 dark:border-gray-700/80">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                                Subjects & Max Marks
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Add or remove subjects and customize max marks for each column.
                            </p>
                        </div>
                        <button type="button" onclick="addSubjectRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm active:scale-95">
                            <i class="fas fa-plus text-xs"></i> Add Subject
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        <table class="w-full text-left text-xs" id="subjectsTable">
                            <thead class="bg-gray-100 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 font-bold uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 w-12 text-center">#</th>
                                    <th class="px-4 py-2.5">Subject Name</th>
                                    <th class="px-4 py-2.5 w-36">Max Marks</th>
                                    <th class="px-4 py-2.5 w-14 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="subjectsTableBody">
                                <!-- Dynamic rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-purple-500/25 flex items-center justify-center gap-3 transition-all transform active:scale-[0.99]">
                        <i class="fas fa-print text-lg"></i>
                        <span>Generate Marking Award List</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Default subjects with their respective default marks (as per standard examination structure)
    const DEFAULT_SUBJECTS = [
        { name: 'ENG', marks: 100 },
        { name: 'ISL', marks: 75 },
        { name: 'SINDHI', marks: 75 },
        { name: 'PHY', marks: 60 },
        { name: 'CHE', marks: 60 },
        { name: 'BIO', marks: 60 },
        { name: 'MATHS', marks: 100 }
    ];

    function renderInitialSubjects() {
        const tbody = document.getElementById('subjectsTableBody');
        tbody.innerHTML = '';
        DEFAULT_SUBJECTS.forEach(subj => {
            addSubjectRow(subj.name, subj.marks);
        });
    }

    function addSubjectRow(name = '', marks = 100) {
        const tbody = document.getElementById('subjectsTableBody');
        const rowCount = tbody.children.length + 1;
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors';

        tr.innerHTML = `
            <td class="px-4 py-2 text-center text-gray-500 row-num font-semibold">${rowCount}</td>
            <td class="px-4 py-2">
                <input type="text" name="subjects[]" value="${name}" placeholder="e.g. ENG or MATHS" required
                    class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-100 uppercase focus:ring-2 focus:ring-purple-500">
            </td>
            <td class="px-4 py-2">
                <input type="number" name="max_marks[]" value="${marks}" min="1" max="1000" required
                    class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-purple-500">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="removeSubjectRow(this)" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Remove Subject">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        updateRowNumbers();
    }

    function removeSubjectRow(btn) {
        const tbody = document.getElementById('subjectsTableBody');
        if (tbody.children.length <= 1) {
            alert('At least one subject is required.');
            return;
        }
        btn.closest('tr').remove();
        updateRowNumbers();
    }

    function updateRowNumbers() {
        const tbody = document.getElementById('subjectsTableBody');
        Array.from(tbody.children).forEach((tr, idx) => {
            const numCell = tr.querySelector('.row-num');
            if (numCell) numCell.textContent = idx + 1;
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', renderInitialSubjects);

    // Export Format Modal Logic
    const markingForm = document.getElementById('markingAwardListForm');

    markingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!markingForm.checkValidity()) {
            markingForm.reportValidity();
            return;
        }
        openExportModal();
    });

    function openExportModal() {
        const modal = document.getElementById('exportFormatModal');
        const content = document.getElementById('exportModalContent');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        });
        document.body.style.overflow = 'hidden';
    }

    function closeExportModal() {
        const modal = document.getElementById('exportFormatModal');
        const content = document.getElementById('exportModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }, 150);
    }

    function submitMarkingAwardList(format) {
        closeExportModal();

        if (format === 'pdf') {
            markingForm.action = 'print_marking_award_list.php';
            markingForm.target = '_blank';
            markingForm.submit();
        } else if (format === 'csv') {
            markingForm.action = '../api/export_marking_award_list_csv.php';
            markingForm.target = 'csvDownloadFrame';
            markingForm.submit();

            // Revert back default action/target for any subsequent direct actions
            setTimeout(() => {
                markingForm.action = 'print_marking_award_list.php';
                markingForm.target = '_blank';
            }, 300);

            if (typeof showToast === 'function') {
                showToast('success', 'Marking award list Excel/CSV download started.');
            }
        }
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('exportFormatModal');
        if (e.target === modal) {
            closeExportModal();
        }
    });
</script>

<!-- Hidden Iframe for Silent CSV Downloads -->
<iframe id="csvDownloadFrame" name="csvDownloadFrame" class="hidden" style="display:none;"></iframe>

<!-- Format Selection Modal -->
<div id="exportFormatModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800 transform transition-all scale-95 duration-200" id="exportModalContent">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg shadow-inner">
                    <i class="fas fa-file-export"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                        Select Export Format
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Choose whether to view printable PDF or download Excel / CSV sheet
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Options Grid -->
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- PDF Option -->
            <button type="button" onclick="submitMarkingAwardList('pdf')" class="group text-left p-5 bg-gradient-to-br from-purple-50/60 to-white dark:from-purple-950/30 dark:to-gray-800/60 rounded-2xl border-2 border-purple-100 dark:border-purple-900/50 hover:border-purple-600 dark:hover:border-purple-500 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-purple-500 flex flex-col justify-between cursor-pointer">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-purple-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300">
                            Printable
                        </span>
                    </div>
                    <h4 class="font-black text-gray-900 dark:text-gray-100 text-base mb-1">
                        PDF Document
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        Full landscape layout with school logo, custom subjects & signature block for print.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-purple-100/60 dark:border-purple-900/40 flex items-center gap-1.5 text-xs font-bold text-purple-600 dark:text-purple-400 group-hover:gap-2.5 transition-all">
                    <span>Open PDF View</span>
                    <i class="fas fa-arrow-right text-[11px]"></i>
                </div>
            </button>

            <!-- Excel / CSV Option -->
            <button type="button" onclick="submitMarkingAwardList('csv')" class="group text-left p-5 bg-gradient-to-br from-emerald-50/60 to-white dark:from-emerald-950/30 dark:to-gray-800/60 rounded-2xl border-2 border-emerald-100 dark:border-emerald-900/50 hover:border-emerald-600 dark:hover:border-emerald-500 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 flex flex-col justify-between cursor-pointer">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-emerald-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300">
                            Spreadsheet
                        </span>
                    </div>
                    <h4 class="font-black text-gray-900 dark:text-gray-100 text-base mb-1">
                        Excel / CSV
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        Spreadsheet with student list and separate columns for each subject and total.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-emerald-100/60 dark:border-emerald-900/40 flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 group-hover:gap-2.5 transition-all">
                    <span>Download CSV</span>
                    <i class="fas fa-download text-[11px]"></i>
                </div>
            </button>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 text-right">
            <button type="button" onclick="closeExportModal()" class="px-5 py-2 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
