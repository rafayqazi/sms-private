<?php
require_once '../includes/auth_session.php';
require_once '../includes/book_db.php';
require_once '../includes/db.php';

if (!isset($_GET['teacher_id'])) {
    header("Location: book_bank.php");
    exit;
}

$teacherId = $_GET['teacher_id'];
$db = new Database();
$bookDb = new BookDatabase();

// Get Teacher Details
$teacher = null;
$csvFile = __DIR__ . '/../data/teachers.csv';
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) == count($header)) {
            $t = array_combine($header, $data);
            if ($t['id'] == $teacherId) {
                $teacher = $t;
                break;
            }
        }
    }
    fclose($handle);
}

if (!$teacher) {
    echo "Teacher not found.";
    exit;
}

$message = '';
$error = '';

// Handle Issue
if (isset($_POST['issue_book'])) {
    $bookId = $_POST['book_id'];
    if ($bookDb->issueBookToTeacher($teacherId, $bookId)) {
        $message = "Book issued successfully to teacher.";
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

$history = $bookDb->getTeacherHistory($teacherId);
$availableBooks = array_filter($bookDb->getAllBooks(), function($b) {
    // Show all books with stock for teachers
    return $b['qty_available'] > 0 && (!isset($b['status']) || $b['status'] == 'active');
});
?>

<?php include '../includes/header.php'; ?>

<div class="mb-6">
    <a href="book_bank.php" class="text-teal-600 hover:text-teal-800 flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i> Back to Book Bank
    </a>

    <div class="bg-white rounded-lg shadow-lg p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></h1>
                <p class="text-gray-500">CNIC: <strong><?php echo htmlspecialchars($teacher['cnic']); ?></strong> | Subject: <strong><?php echo htmlspecialchars($teacher['subject'] ?? 'N/A'); ?></strong></p>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

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
                                <p class="text-xs text-gray-500">Subject: <?php echo htmlspecialchars($record['book_details']['subject'] ?? '-'); ?> | Class: <?php echo htmlspecialchars($record['book_details']['class'] ?? '-'); ?></p>
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
            <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Book</label>
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
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full">
        <form method="POST" class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Return Book</h3>
            <p id="returnBookName" class="text-gray-600 mb-4 text-sm"></p>
            
            <input type="hidden" name="issue_id" id="returnIssueId">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks (Condition etc.)</label>
                <textarea name="remarks" rows="2" class="w-full border border-gray-300 rounded-md p-2 text-sm" placeholder="e.g. Good condition, Torn page..."></textarea>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('returnModal').classList.add('hidden')" class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                <button type="submit" name="return_book" class="px-3 py-1.5 bg-red-600 text-white rounded hover:bg-red-700">Confirm Return</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReturnModal(issueId, bookName) {
        document.getElementById('returnIssueId').value = issueId;
        document.getElementById('returnBookName').textContent = `Returning: ${bookName}`;
        document.getElementById('returnModal').classList.remove('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
