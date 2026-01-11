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
                <form action="print_slips.php" method="GET" target="_blank" class="space-y-6">
                    <input type="hidden" name="class" value="<?php echo htmlspecialchars($selectedClass); ?>">
                    
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
                    </script>

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                        <div>
                            <h4 class="font-medium text-blue-800">Information</h4>
                            <p class="text-sm text-blue-600 mt-1">
                                Clicking "Generate Slips" will open a printable PDF view for all students in 
                                <strong>Class <?php echo htmlspecialchars($selectedClass); ?></strong>.
                            </p>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                        <i class="fas fa-id-card"></i> Generate Slips
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
