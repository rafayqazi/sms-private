<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();
// Admin/Super Admin can see all classes, others restricted
$allowedClasses = getAssignedClasses();

// If coming from a specific class selection
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-print text-indigo-600"></i> Print Examination Slips
        </h2>

        <form action="" method="GET" class="space-y-6" id="slipForm">
            <div class="grid grid-cols-1 gap-6">
                <!-- Class Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
                    <select name="class" id="classSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" required onchange="this.form.submit()">
                        <option value="">Select Class</option>
                        <?php foreach ($allowedClasses as $class): ?>
                            <option value="<?php echo $class; ?>" <?php echo $selectedClass === $class ? 'selected' : ''; ?>>
                                Class <?php echo $class; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <?php if ($selectedClass): ?>
            <div class="mt-8 border-t pt-6" id="generateSection">
                <form action="print_slips.php" method="GET" target="_blank" class="space-y-6" id="slipsFormMain">
                    <input type="hidden" name="class" value="<?php echo htmlspecialchars($selectedClass); ?>">
                    <input type="hidden" name="slips_per_page" id="slipsPerPageInput" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Examination Name</label>
                        <input type="text" name="exam_name" placeholder="e.g. Mid Term Examination 2025" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Datesheet Input Section -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                            <span>Datesheet Configuration</span>
                            <button type="button" onclick="addSubjectRow()" class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition">
                                <i class="fas fa-plus"></i> Add Subject
                            </button>
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left" id="datesheetTable">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2">Subject</th>
                                        <th class="px-3 py-2">Date</th>
                                        <th class="px-3 py-2">Day</th>
                                        <th class="px-3 py-2">Time</th>
                                        <th class="px-3 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <!-- Dynamic Rows will be added here -->
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Select a date to automatically calculate the day.</p>
                    </div>

                    <script>
                        const DEFAULT_SUBJECTS = ['ENG', 'MATH', 'Social Studies', 'G.Science', 'MT', 'Islamyat', 'NMT'];

                        function addSubjectRow(subjectName = '', dateValue = '', timeValue = '') {
                            const tbody = document.querySelector('#datesheetTable tbody');
                            const row = document.createElement('tr');
                            row.className = 'hover:bg-gray-50';
                            
                            // If dateValue is provided, calculate day
                            let dayValue = '';
                            if (dateValue) {
                                const d = new Date(dateValue);
                                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                dayValue = days[d.getDay()];
                            }

                            row.innerHTML = `
                                <td class="px-2 py-2">
                                    <input type="text" name="subjects[]" value="${subjectName}" placeholder="Subject Name" required class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-indigo-500 bg-transparent">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="date" name="dates[]" value="${dateValue}" required onchange="handleDateChange(this)" class="date-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-indigo-500 bg-transparent">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" name="days[]" value="${dayValue}" readonly placeholder="Day" class="day-input w-full px-2 py-1 bg-gray-100 border rounded cursor-not-allowed text-gray-500">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" name="times[]" value="${timeValue}" placeholder="e.g. 9:00 AM" required onchange="handleTimeChange(this)" class="time-input w-full px-2 py-1 border rounded focus:ring-2 focus:ring-indigo-500 bg-transparent">
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        }

                        function handleDateChange(input) {
                            calculateDay(input);
                            
                            // Check if this is the FIRST date input
                            const allDateInputs = document.querySelectorAll('.date-input');
                            if (input === allDateInputs[0]) {
                                autoFillDates(input.value);
                            }
                        }

                        function handleTimeChange(input) {
                            // Check if this is the FIRST time input
                            const allTimeInputs = document.querySelectorAll('.time-input');
                            if (input === allTimeInputs[0]) {
                                const newValue = input.value;
                                // Update all other time inputs
                                for(let i = 1; i < allTimeInputs.length; i++) {
                                    allTimeInputs[i].value = newValue;
                                }
                            }
                        }

                        function calculateDay(dateInput) {
                            const date = new Date(dateInput.value);
                            const row = dateInput.closest('tr');
                            const dayInput = row.querySelector('.day-input');
                            
                            if (!isNaN(date.getTime())) {
                                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                dayInput.value = days[date.getDay()];
                            } else {
                                dayInput.value = '';
                            }
                        }

                        function autoFillDates(startDateStr) {
                            if (!startDateStr) return;
                            
                            const startDate = new Date(startDateStr);
                            const allDateInputs = document.querySelectorAll('.date-input');
                            
                            let currentDate = new Date(startDate);

                            // Start from the second row (index 1)
                            for(let i = 1; i < allDateInputs.length; i++) {
                                // Increment date by 1 day
                                currentDate.setDate(currentDate.getDate() + 1);
                                
                                // Check if it's Sunday (0), if so, add another day
                                if (currentDate.getDay() === 0) {
                                    currentDate.setDate(currentDate.getDate() + 1);
                                }
                                
                                // Format to YYYY-MM-DD
                                const yyyy = currentDate.getFullYear();
                                const mm = String(currentDate.getMonth() + 1).padStart(2, '0');
                                const dd = String(currentDate.getDate()).padStart(2, '0');
                                const formattedDate = `${yyyy}-${mm}-${dd}`;
                                
                                allDateInputs[i].value = formattedDate;
                                // Update the day column for this row too
                                calculateDay(allDateInputs[i]);
                            }
                        }

                        // Initialize default rows
                        document.addEventListener('DOMContentLoaded', () => {
                            const tbody = document.querySelector('#datesheetTable tbody');
                            if (tbody.children.length === 0) {
                                DEFAULT_SUBJECTS.forEach(sub => addSubjectRow(sub));
                            }
                        });

                        function triggerSlipGeneration() {
                            const form = document.getElementById('slipsFormMain');
                            if (!form.reportValidity()) {
                                return;
                            }
                            openLayoutModal();
                        }

                        function openLayoutModal() {
                            const modal = document.getElementById('printLayoutModal');
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeLayoutModal() {
                            const modal = document.getElementById('printLayoutModal');
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                            document.body.style.overflow = '';
                        }

                        function selectLayoutAndGenerate(slipsPerPage) {
                            document.getElementById('slipsPerPageInput').value = slipsPerPage;
                            closeLayoutModal();
                            document.getElementById('slipsFormMain').submit();
                        }
                    </script>

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                        <div>
                            <h4 class="font-medium text-blue-800">Information</h4>
                            <p class="text-sm text-blue-600 mt-1">
                                Clicking "Generate Slips" will open the layout selector (1 Slip or 2 Slips per page) for 
                                <strong>Class <?php echo htmlspecialchars($selectedClass); ?></strong>.
                            </p>
                        </div>
                    </div>

                    <button type="button" onclick="triggerSlipGeneration()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg shadow-indigo-200/50 hover:shadow-indigo-300 flex items-center justify-center gap-2 text-base">
                        <i class="fas fa-id-card text-lg"></i> Generate Slips
                    </button>
                </form>
            </div>

            <!-- Print Layout Selection Modal -->
            <div id="printLayoutModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left">
                <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden animate-[scaleIn_0.25s_ease-out] border border-gray-100">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-slate-50/50">
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm">
                                    <i class="fas fa-print"></i>
                                </span>
                                Select Print Layout
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Choose how many slips to print per page</p>
                        </div>
                        <button type="button" onclick="closeLayoutModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Option 1: 1 Slip per page -->
                        <button type="button" onclick="selectLayoutAndGenerate(1)" 
                            class="group text-left p-5 rounded-2xl border-2 border-gray-200 hover:border-indigo-600 hover:bg-indigo-50/40 transition-all flex flex-col justify-between relative focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                        <i class="fas fa-file-lines"></i>
                                    </div>
                                    <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700">Standard</span>
                                </div>
                                <h4 class="font-extrabold text-gray-900 text-base group-hover:text-indigo-600 transition-colors">1 Slip Per Page</h4>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                    Full size A4 slip for each student with large tables & details.
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center text-xs font-bold text-indigo-600">
                                <span>Select 1 Per Page</span>
                                <i class="fas fa-arrow-right ml-auto group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </button>

                        <!-- Option 2: 2 Slips per page -->
                        <button type="button" onclick="selectLayoutAndGenerate(2)" 
                            class="group text-left p-5 rounded-2xl border-2 border-emerald-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex flex-col justify-between relative focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-emerald-50/20">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                        <i class="fas fa-copy"></i>
                                    </div>
                                    <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">Paper Saver 🌿</span>
                                </div>
                                <h4 class="font-extrabold text-gray-900 text-base group-hover:text-emerald-700 transition-colors">2 Slips Per Page</h4>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                    2 student slips on 1 page with cut line. Saves 50% paper!
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-emerald-100 flex items-center text-xs font-bold text-emerald-700">
                                <span>Select 2 Per Page</span>
                                <i class="fas fa-arrow-right ml-auto group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </button>
                    </div>

                    <div class="p-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" onclick="closeLayoutModal()" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

