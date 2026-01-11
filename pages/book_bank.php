<?php
require_once '../includes/auth_session.php';
require_once '../includes/book_db.php';
require_once '../includes/db.php'; // For getting classes/students if needed

$bookDb = new BookDatabase();
$message = '';
$error = '';

// Handle Add Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
    $name = $_POST['name'];
    $subject = $_POST['subject'];
    $class = $_POST['class'];
    $qty = (int)$_POST['qty'];
    
    if ($bookDb->addBook($name, $subject, $class, $qty)) {
        $message = "Book added successfully!";
    } else {
        $error = "Failed to add book.";
    }
}

$books = $bookDb->getAllBooks();
$stats = $bookDb->getStats();
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-teal-600 to-teal-800 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">Book Bank</h1>
        <p class="text-teal-100 mt-1">Manage Govt. Free Textbooks</p>
    </div>
    <div class="flex gap-2 w-full md:w-auto">
        <button onclick="document.getElementById('addBookModal').classList.remove('hidden')" class="w-full md:w-auto bg-white/20 backdrop-blur-sm text-white px-6 py-2 rounded-lg hover:bg-white/30 transition-colors font-semibold flex items-center justify-center gap-2">
            <i class="fas fa-plus-circle"></i> Add New Book
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="#inventoryTable" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-teal-500 hover:shadow-lg transition-shadow cursor-pointer">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium uppercase">Total Books</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['total_books']; ?></h3>
            </div>
            <div class="bg-teal-100 p-3 rounded-full">
                <i class="fas fa-book text-teal-600 text-xl"></i>
            </div>
        </div>
    </a>
    <a href="book_bank_report.php?type=issued" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500 hover:shadow-lg transition-shadow cursor-pointer">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium uppercase">Currently Issued</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['issued_books']; ?></h3>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
                <i class="fas fa-hand-holding text-orange-600 text-xl"></i>
            </div>
        </div>
    </a>
    <a href="#inventoryTable" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition-shadow cursor-pointer">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium uppercase">In Stock</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['available_books']; ?></h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </a>
</div>

<!-- Search Student Section -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Issue / Return Books</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <div class="relative">
                <input type="text" id="studentSearch" placeholder="Search student by Name or GR No to issue/return books..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 pl-10">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
            <!-- Search Results Dropdown -->
            <div id="searchResults" class="hidden absolute z-10 w-full bg-white mt-1 rounded-md shadow-lg max-h-60 overflow-y-auto border border-gray-200"></div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div id="inventoryTable" class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">Current Inventory</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                <tr>
                    <th class="p-4">Class</th>
                    <th class="p-4">Subject</th>
                    <th class="p-4">Book Name</th>
                    <th class="p-4 text-center">Total</th>
                    <th class="p-4 text-center">Available</th>
                    <th class="p-4 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($books)): ?>
                    <tr><td colspan="6" class="p-4 text-center text-gray-500">No books in inventory.</td></tr>
                <?php else: ?>
                    <?php 
                    // Sort books by class then subject
                    usort($books, function($a, $b) {
                        return strcmp($a['class'], $b['class']) ?: strcmp($a['subject'], $b['subject']);
                    });
                    foreach ($books as $book): 
                        $statusClass = $book['qty_available'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                        $statusText = $book['qty_available'] > 0 ? 'In Stock' : 'Out of Stock';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 font-medium text-gray-600"><?php echo htmlspecialchars($book['class']); ?></td>
                        <td class="p-4 text-gray-800"><?php echo htmlspecialchars($book['subject']); ?></td>
                        <td class="p-4 text-gray-600"><?php echo htmlspecialchars($book['name']); ?></td>
                        <td class="p-4 text-center font-bold text-gray-700"><?php echo htmlspecialchars($book['qty_total']); ?></td>
                        <td class="p-4 text-center font-bold text-teal-600"><?php echo htmlspecialchars($book['qty_available']); ?></td>
                        <td class="p-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Book Modal -->
<div id="addBookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <form method="POST" class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Add New Book</h3>
                <button type="button" onclick="document.getElementById('addBookModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <select name="class" required class="w-full border border-gray-300 rounded-md p-2">
                        <?php 
                        $db = new Database(); 
                        foreach ($db->getClassNames() as $c): 
                        ?>
                            <option value="<?php echo $c; ?>">Class <?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" required class="w-full border border-gray-300 rounded-md p-2" placeholder="e.g. English">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Book Name / Title</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-md p-2" placeholder="e.g. English Textbook Class 4">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Received</label>
                    <input type="number" name="qty" min="1" required class="w-full border border-gray-300 rounded-md p-2">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addBookModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="submit" name="add_book" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700">Add Book</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('studentSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    const resultsDiv = document.getElementById('searchResults');
    
    if (query.length < 2) {
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
        return;
    }

    // Debounce later, for now direct fetch
    fetch(`../api/get_students.php?search=${encodeURIComponent(query)}&limit=5`) // Using existing API?? format might be different. 
    // Wait, get_students returns HTML rows usually. I need JSON.
    // Let's create a quick inline logic or reuse existing db method? 
    // Actually, check what get_students.php returns. It returns HTML table rows.
    // I need a JSON endpoint or parse HTML? JSON is better.
    // Let's use a new endpoint or inline PHP logic if simpler?
    // Let's create a simple API endpoint for this: api/search_student_json.php
    
    // For now, let's implement the fetch to a new file I'll create `api/search_students.php`
    fetch(`../api/search_students.php?query=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            if (data.length > 0) {
                resultsDiv.classList.remove('hidden');
                data.forEach(student => {
                    const div = document.createElement('div');
                    div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0';
                    div.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-gray-800">${student.student_name}</p>
                                <p class="text-xs text-gray-500">GR: ${student.gr_no} | Class: ${student.current_class}</p>
                            </div>
                            <span class="text-teal-600"><i class="fas fa-chevron-right"></i></span>
                        </div>
                    `;
                    div.onclick = () => {
                        window.location.href = `book_bank_actions.php?student_id=${student.id}`;
                    };
                    resultsDiv.appendChild(div);
                });
            } else {
                resultsDiv.classList.remove('hidden');
                resultsDiv.innerHTML = '<div class="p-3 text-gray-500 text-sm">No students found</div>';
            }
        });
});

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    if (!document.getElementById('studentSearch').contains(e.target)) {
        document.getElementById('searchResults').classList.add('hidden');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
