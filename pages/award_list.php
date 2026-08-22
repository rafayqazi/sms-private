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
                        <input type="number" name="starting_seat_no" placeholder="e.g. 901 (Leave empty to use GR No / Roll No)" min="1"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        If entered (e.g. 901), students will get sequential seat numbers 901, 902, 903... Otherwise, their GR numbers will be used.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-3 transition-all transform active:scale-[0.99]">
                        <i class="fas fa-print text-lg"></i>
                        <span>Generate Award List</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
