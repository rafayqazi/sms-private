<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
// Admin/Super Admin can see all classes, others restricted
$allowedClasses = getAssignedClasses();
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Breadcrumb / Back Link -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="../index.php" class="hover:text-indigo-600 transition-colors"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                <span>Examination</span>
                <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                <span class="text-gray-800 dark:text-gray-200 font-semibold">Award List</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-6 md:p-8">
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100 dark:border-gray-800">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-2xl shrink-0 shadow-inner">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-black text-gray-800 dark:text-gray-100 tracking-tight">
                        Generate Examination Award List
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Fill in the details below to generate and print the official student award list sheet.
                    </p>
                </div>
            </div>

            <form action="print_award_list.php" method="GET" target="_blank" class="space-y-5" id="awardListForm">
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
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                    </div>
                </div>

                <!-- Class & Subject Row -->
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
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                                <option value="">Select Class</option>
                                <?php foreach ($allowedClasses as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $selectedClass === $class ? 'selected' : ''; ?>>
                                        Class <?php echo htmlspecialchars($class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Subject <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <input type="text" name="subject" list="common-subjects" placeholder="e.g. MATHEMATICS" required
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium uppercase">
                            <datalist id="common-subjects">
                                <option value="MATHEMATICS">
                                <option value="ENGLISH">
                                <option value="URDU">
                                <option value="SINDHI">
                                <option value="GENERAL SCIENCE">
                                <option value="ISLAMIAT">
                                <option value="SOCIAL STUDIES">
                                <option value="PAKISTAN STUDIES">
                                <option value="PHYSICS">
                                <option value="CHEMISTRY">
                                <option value="BIOLOGY">
                                <option value="COMPUTER SCIENCE">
                            </datalist>
                        </div>
                    </div>
                </div>

                <!-- Date & Max Marks Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Date -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                        </div>
                    </div>

                    <!-- Max Marks -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Max Marks <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-award"></i>
                            </div>
                            <input type="number" name="max_marks" value="100" min="1" max="1000" placeholder="e.g. 100" required
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                        </div>
                    </div>
                </div>

                <!-- Starting Seat Number (Optional) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                        Starting Seat # <span class="text-xs font-normal text-gray-400">(Optional)</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <input type="number" name="starting_seat_no" placeholder="e.g. 1 (Default: starts at 1, e.g. CLASS-1, CLASS-2...)" min="1"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        Seat numbers match Exam Slips format (e.g. CLASS-1, CLASS-2...) sorted with Boys first, then Girls by GR#.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-3 transition-all transform active:scale-[0.99]">
                        <i class="fas fa-file-export text-lg"></i>
                        <span>Generate Award List</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Iframe for Silent CSV Downloads -->
<iframe id="csvDownloadFrame" name="csvDownloadFrame" class="hidden" style="display:none;"></iframe>

<!-- Format Selection Modal -->
<div id="exportFormatModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800 transform transition-all scale-95 duration-200" id="exportModalContent">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg shadow-inner">
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
            <button type="button" onclick="submitAwardList('pdf')" class="group text-left p-5 bg-gradient-to-br from-indigo-50/60 to-white dark:from-indigo-950/30 dark:to-gray-800/60 rounded-2xl border-2 border-indigo-100 dark:border-indigo-900/50 hover:border-indigo-600 dark:hover:border-indigo-500 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 flex flex-col justify-between cursor-pointer">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-indigo-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md shadow-indigo-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">
                            Printable
                        </span>
                    </div>
                    <h4 class="font-black text-gray-900 dark:text-gray-100 text-base mb-1">
                        PDF Document
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        Official award list layout with school branding & signatures ready for printing.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-indigo-100/60 dark:border-indigo-900/40 flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 group-hover:gap-2.5 transition-all">
                    <span>Open PDF View</span>
                    <i class="fas fa-arrow-right text-[11px]"></i>
                </div>
            </button>

            <!-- Excel / CSV Option -->
            <button type="button" onclick="submitAwardList('csv')" class="group text-left p-5 bg-gradient-to-br from-emerald-50/60 to-white dark:from-emerald-950/30 dark:to-gray-800/60 rounded-2xl border-2 border-emerald-100 dark:border-emerald-900/50 hover:border-emerald-600 dark:hover:border-emerald-500 transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 flex flex-col justify-between cursor-pointer">
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
                        Editable spreadsheet with student names, seat numbers & blank marks column.
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

<script>
    const awardForm = document.getElementById('awardListForm');

    awardForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!awardForm.checkValidity()) {
            awardForm.reportValidity();
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

    function submitAwardList(format) {
        closeExportModal();

        if (format === 'pdf') {
            awardForm.action = 'print_award_list.php';
            awardForm.target = '_blank';
            awardForm.submit();
        } else if (format === 'csv') {
            awardForm.action = '../api/export_award_list_csv.php';
            awardForm.target = 'csvDownloadFrame';
            awardForm.submit();

            // Revert back default action/target for any subsequent direct actions
            setTimeout(() => {
                awardForm.action = 'print_award_list.php';
                awardForm.target = '_blank';
            }, 300);

            if (typeof showToast === 'function') {
                showToast('success', 'Award list Excel/CSV download started.');
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

<?php include '../includes/footer.php'; ?>
