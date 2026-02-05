<?php
require_once '../includes/auth_session.php';
require_once '../includes/book_db.php';
require_once '../includes/db.php';

if (!isset($_GET['student_id'])) {
    header("Location: book_bank.php");
    exit;
}

$studentId = $_GET['student_id'];
$db = new Database();
$bookDb = new BookDatabase();

// Get Student Details
$student = $db->getStudent($studentId);

if (!$student) {
    echo "Student not found.";
    exit;
}

$message = '';
$error = '';

// Handle Issue
if (isset($_POST['issue_book'])) {
    $bookId = $_POST['book_id'];
    if ($bookDb->issueBook($studentId, $bookId)) {
        $message = "Book issued successfully.";
    } else {
        $error = "Failed to issue book (Out of Stock or Invalid).";
    }
}

// Handle Return
if (isset($_POST['return_book'])) {
    $issueId = $_POST['issue_id'];
    $condition = $_POST['condition'] ?? 'normal';
    $damageType = $_POST['damage_type'] ?? '';
    $damageRemarks = $_POST['damage_remarks'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    
    if ($bookDb->returnBook($issueId, $condition, $damageType, $damageRemarks, $remarks)) {
        $message = "Book returned successfully.";
    } else {
        $error = "Failed to return book.";
    }
}

$history = $bookDb->getStudentHistory($studentId);
// Library Mode: Show ALL available books regardless of student class
$availableBooks = array_filter($bookDb->getAllBooks(), function($b) {
    return $b['qty_available'] > 0 && (!isset($b['status']) || $b['status'] == 'active');
});
?>

<?php include '../includes/header.php'; ?>

<?php if ($message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <p class="font-medium"><?php echo htmlspecialchars($error); ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="mb-6">
    <a href="book_bank.php" class="text-teal-600 hover:text-teal-800 flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i> Back to Inventory
    </a>

    <div class="bg-white rounded-lg shadow-lg p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 text-2xl font-bold">
                <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($student['student_name']); ?></h1>
                <p class="text-gray-500">GR No: <strong><?php echo htmlspecialchars($student['gr_no']); ?></strong> | Class: <strong><?php echo htmlspecialchars($student['current_class']); ?></strong></p>
            </div>
        </div>
        <div>
           <!-- Stats if needed -->
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Issue Book & Current Books -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Currently Issued -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-orange-50">
                <h2 class="font-bold text-orange-800 flex items-center gap-2">
                    <i class="fas fa-book-reader"></i> Currently Issued Books
                </h2>
            </div>
            <div class="p-4">
                <?php 
                $currentBooks = array_filter($history, function($h) { return $h['status'] == 'Issued'; });
                if (empty($currentBooks)): 
                ?>
                    <p class="text-gray-500 italic text-center py-4">No books currently issued.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($currentBooks as $record): ?>
                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg p-3 hover:shadow-sm transition-shadow">
                            <div>
                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($record['book_details']['name'] ?? 'Unknown Book'); ?></h3>
                                <p class="text-xs text-gray-500">Subject: <?php echo htmlspecialchars($record['book_details']['subject'] ?? '-'); ?></p>
                                <p class="text-xs text-orange-600 mt-1">Issued: <?php echo $record['issue_date']; ?></p>
                            </div>
                            <button onclick="openReturnModal('<?php echo $record['id']; ?>', '<?php echo htmlspecialchars($record['book_details']['name'] ?? ''); ?>')" class="px-3 py-1 bg-red-100 text-red-600 rounded text-sm font-medium hover:bg-red-200">
                                Return
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Issue New Book -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle text-teal-600"></i> Issue New Book
            </h2>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-4 rounded">
                <p class="text-sm text-blue-800 flex items-center gap-2">
                    <i class="fas fa-book-reader"></i>
                    <strong>Library Mode:</strong> Any book from any class can be issued!
                </p>
            </div>
            <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Book (Any Class)</label>
                    <select name="book_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500" required>
                        <option value="">-- Choose Book --</option>
                        <?php foreach ($availableBooks as $book): ?>
                            <option value="<?php echo $book['id']; ?>">
                                Class <?php echo htmlspecialchars($book['class']); ?> - <?php echo htmlspecialchars($book['subject']); ?> - <?php echo htmlspecialchars($book['name']); ?> (<?php echo $book['qty_available']; ?> left)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="issue_book" class="w-full md:w-auto px-6 py-2 bg-teal-600 text-white rounded-lg font-semibold hover:bg-teal-700 transition-colors">
                    Issue
                </button>
            </form>
            <?php if (empty($availableBooks)): ?>
                <p class="text-xs text-red-500 mt-2">No books available in inventory.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Return History -->
    <div class="bg-white rounded-lg shadow-md h-full">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-history"></i> Return History
            </h2>
        </div>
        <div class="p-4 max-h-[500px] overflow-y-auto">
             <?php 
            $returnedBooks = array_filter($history, function($h) { return $h['status'] == 'Returned'; });
            if (empty($returnedBooks)): 
            ?>
                <p class="text-gray-500 italic text-center py-4">No history found.</p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($returnedBooks as $record): ?>
                    <li class="border-l-2 border-green-500 pl-4 py-1">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($record['book_details']['name'] ?? 'Unknown'); ?></p>
                        <div class="text-xs text-gray-500 flex justify-between mt-1">
                            <span>Issued: <?php echo $record['issue_date']; ?></span>
                            <span>Returned: <?php echo $record['return_date']; ?></span>
                        </div>
                        <?php if ($record['remarks']): ?>
                            <p class="text-xs text-gray-600 italic mt-1">"<?php echo htmlspecialchars($record['remarks']); ?>"</p>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div id="returnModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <form method="POST" class="p-6" id="returnForm">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Return Book</h3>
            <p id="returnBookName" class="text-gray-600 mb-4 text-sm"></p>
            
            <input type="hidden" name="issue_id" id="returnIssueId">
            
            <!-- Book Condition -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Book Condition <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-500 has-[:checked]:border-green-500 has-[:checked]:bg-green-50 transition-all">
                        <input type="radio" name="condition" value="normal" checked class="sr-only" onchange="toggleDamageFields(false)">
                        <span class="flex items-center gap-2 font-medium text-gray-700">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i> Normal
                        </span>
                    </label>
                    <label class="flex items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-red-500 has-[:checked]:border-red-500 has-[:checked]:bg-red-50 transition-all">
                        <input type="radio" name="condition" value="damaged" class="sr-only" onchange="toggleDamageFields(true)">
                        <span class="flex items-center gap-2 font-medium text-gray-700">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i> Damaged
                        </span>
                    </label>
                </div>
            </div>

            <!-- Damage Details (Hidden by default) -->
            <div id="damageFields" class="mb-4 hidden">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-3">
                    <p class="text-sm text-red-700 flex items-center gap-2 font-medium mb-2">
                        <i class="fas fa-info-circle"></i> Warning
                    </p>
                    <p class="text-xs text-red-600">
                        Damaged books will NOT return to stock. They will be moved to damaged inventory.
                    </p>
                </div>
                
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Damage Type <span class="text-red-500">*</span>
                </label>
                <select name="damage_type" id="damageType" class="w-full border border-gray-300 rounded-lg p-2 mb-3">
                    <option value="">-- Select Damage Type --</option>
                    <option value="torn_pages">Torn Pages</option>
                    <option value="water_damage">Water Damage</option>
                    <option value="missing_pages">Missing Pages</option>
                    <option value="cover_damage">Cover Damage</option>
                    <option value="writing_scribbles">Writing/Scribbles</option>
                    <option value="binding_broken">Binding Broken</option>
                    <option value="other">Other</option>
                </select>
                
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Damage Details <span class="text-red-500">*</span>
                </label>
                <textarea name="damage_remarks" id="damageRemarks" rows="2" class="w-full border border-gray-300 rounded-lg p-2" placeholder="Describe the damage in detail..."></textarea>
            </div>

            <!-- General Remarks -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    General Remarks <span class="text-gray-400">(optional)</span>
                </label>
                <textarea name="remarks" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="e.g. Late return, Notes..."></textarea>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeReturnModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" name="return_book" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <i class="fas fa-undo"></i> Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReturnModal(issueId, bookName) {
        document.getElementById('returnIssueId').value = issueId;
        document.getElementById('returnBookName').textContent = `Returning: ${bookName}`;
        document.getElementById('returnModal').classList.remove('hidden');
        
        // Reset form
        document.getElementById('returnForm').reset();
        document.getElementById('damageFields').classList.add('hidden');
    }
    
    function closeReturnModal() {
        document.getElementById('returnModal').classList.add('hidden');
        document.getElementById('returnForm').reset();
    }
    
    function toggleDamageFields(show) {
        const damageFields = document.getElementById('damageFields');
        const damageType = document.getElementById('damageType');
        const damageRemarks = document.getElementById('damageRemarks');
        
        if (show) {
            damageFields.classList.remove('hidden');
            damageType.required = true;
            damageRemarks.required = true;
        } else {
            damageFields.classList.add('hidden');
            damageType.required = false;
            damageRemarks.required = false;
            damageType.value = '';
            damageRemarks.value = '';
        }
    }
    
    // Form validation
    document.getElementById('returnForm').addEventListener('submit', function(e) {
        const condition = document.querySelector('input[name="condition"]:checked').value;
        
        if (condition === 'damaged') {
            const damageType = document.getElementById('damageType').value;
            const damageRemarks = document.getElementById('damageRemarks').value.trim();
            
            if (!damageType) {
                e.preventDefault();
                alert('Please select a damage type.');
                return false;
            }
            
            if (!damageRemarks) {
                e.preventDefault();
                alert('Please describe the damage in detail.');
                return false;
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
