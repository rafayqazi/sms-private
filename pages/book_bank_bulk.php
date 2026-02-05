<?php
require_once '../includes/auth_session.php';
require_once '../includes/book_db.php';
require_once '../includes/db.php';

$bookDb = new BookDatabase();
$db = new Database();

$message = '';
$error = '';
$result = null;

// Handle Bulk Issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_issue'])) {
    $classId = $_POST['class'];
    $bookId = $_POST['book_id'];
    
    $result = $bookDb->issueBookBulk($classId, $bookId);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

$books = $bookDb->getAllBooks();
$classes = $db->getClassNames();
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white p-6 rounded-lg shadow-lg mb-6">
    <div class="flex items-center gap-3">
        <a href="book_bank.php" class="text-white hover:text-purple-100">
            <i class="fas fa-arrow-left text-2xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold">Bulk Book Issue</h1>
            <p class="text-purple-100 mt-1">Issue books to entire class at once</p>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Select Class and Book</h2>
    
    <form method="POST" id="bulkIssueForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
                <select name="class" id="classSelect" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Choose Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Book</label>
                <select name="book_id" id="bookSelect" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Choose Book --</option>
                </select>
            </div>
        </div>
        
        <div id="previewSection" class="hidden mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3">Preview</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Class</p>
                        <p id="previewClass" class="font-bold text-gray-800"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Book</p>
                        <p id="previewBook" class="font-bold text-gray-800"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Available Stock</p>
                        <p id="previewStock" class="font-bold text-teal-600"></p>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-yellow-600 mt-1 mr-2"></i>
                        <div class="text-sm text-yellow-800">
                            <p class="font-bold">Note:</p>
                            <p>This will issue books to all students in the selected class who don't already have this book. Students who already have the book will be skipped.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <a href="book_bank.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" name="bulk_issue" id="submitBtn" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold flex items-center gap-2">
                <i class="fas fa-users"></i> Issue to Class
            </button>
        </div>
    </form>
</div>

<script>
const books = <?php echo json_encode($books); ?>;
const classSelect = document.getElementById('classSelect');
const bookSelect = document.getElementById('bookSelect');
const previewSection = document.getElementById('previewSection');

classSelect.addEventListener('change', function() {
    const selectedClass = this.value;
    bookSelect.innerHTML = '<option value="">-- Choose Book --</option>';
    
    if (selectedClass) {
        // Filter books by class
        const classBooks = books.filter(b => b.class === selectedClass && parseInt(b.qty_available) > 0);
        
        classBooks.forEach(book => {
            const option = document.createElement('option');
            option.value = book.id;
            option.textContent = `${book.subject} - ${book.name} (${book.qty_available} available)`;
            option.dataset.name = book.name;
            option.dataset.stock = book.qty_available;
            bookSelect.appendChild(option);
        });
    }
    
    updatePreview();
});

bookSelect.addEventListener('change', updatePreview);

function updatePreview() {
    const selectedClass = classSelect.value;
    const selectedBook = bookSelect.options[bookSelect.selectedIndex];
    
    if (selectedClass && selectedBook.value) {
        previewSection.classList.remove('hidden');
        document.getElementById('previewClass').textContent = selectedClass;
        document.getElementById('previewBook').textContent = selectedBook.dataset.name;
        document.getElementById('previewStock').textContent = selectedBook.dataset.stock + ' books';
    } else {
        previewSection.classList.add('hidden');
    }
}

// Confirmation before submit
document.getElementById('bulkIssueForm').addEventListener('submit', function(e) {
    const selectedClass = classSelect.value;
    const selectedBook = bookSelect.options[bookSelect.selectedIndex];
    
    if (!confirm(`Are you sure you want to issue "${selectedBook.dataset.name}" to all students in Class ${selectedClass}?`)) {
        e.preventDefault();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
