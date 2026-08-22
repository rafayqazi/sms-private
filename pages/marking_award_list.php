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
                            <input type="number" name="starting_seat_no" placeholder="e.g. 901 (Leave empty to use GR No)" min="1"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all text-sm font-medium">
                        </div>
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
</script>

<?php include '../includes/footer.php'; ?>
