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

// Handle Delete Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_book'])) {
    $bookId = $_POST['book_id'];
    if ($bookDb->deleteBook($bookId)) {
        $message = "Book deleted successfully!";
    } else {
        $error = "Failed to delete book.";
    }
}

// Handle Move to Damaged
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['move_to_damaged'])) {
    $bookId = $_POST['book_id'];
    $quantity = (int)$_POST['quantity'];
    $damageType = $_POST['damage_type'];
    $remarks = $_POST['remarks'];
    
    if ($bookDb->moveToDamaged($bookId, $quantity, $damageType, $remarks)) {
        $message = "Books moved to damaged inventory successfully!";
    } else {
        $error = "Failed to move books to damaged inventory.";
    }
}

$books = $bookDb->getAllBooks();
$stats = $bookDb->getStats();
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
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
    <a href="book_bank_damaged.php" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500 hover:shadow-lg transition-shadow cursor-pointer">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium uppercase">Damaged</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['damaged_books']; ?></h3>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
    </a>
</div>

<!-- Search Student Section -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Issue / Return Books</h2>
    
    <!-- Tab Navigation -->
    <div class="flex border-b border-gray-200 mb-6">
        <button onclick="switchTab('individual')" id="tab-individual" class="tab-btn px-6 py-3 font-semibold text-teal-600 border-b-2 border-teal-600">
            <i class="fas fa-user mr-2"></i>Individual Student
        </button>
        <button onclick="switchTab('bulk')" id="tab-bulk" class="tab-btn px-6 py-3 font-semibold text-gray-500 hover:text-teal-600">
            <i class="fas fa-users mr-2"></i>Bulk Issue
        </button>
        <button onclick="switchTab('teacher')" id="tab-teacher" class="tab-btn px-6 py-3 font-semibold text-gray-500 hover:text-teal-600">
            <i class="fas fa-chalkboard-teacher mr-2"></i>Teacher
        </button>
    </div>
    
    <!-- Tab Content: Individual Student -->
    <div id="content-individual" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 relative">
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
    
    <!-- Tab Content: Bulk Issue -->
    <div id="content-bulk" class="tab-content hidden">
        <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded mb-4">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-purple-600 mt-1 mr-3"></i>
                <div>
                    <p class="font-bold text-purple-800">Bulk Book Issue</p>
                    <p class="text-sm text-purple-700">Issue books to all students in a class at once. Click the button below to proceed.</p>
                </div>
            </div>
        </div>
        <a href="book_bank_bulk.php" class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold transition-colors">
            <i class="fas fa-users"></i> Go to Bulk Issue Page
        </a>
    </div>
    
    <!-- Tab Content: Teacher -->
    <div id="content-teacher" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" id="teacherSearch" placeholder="Search teacher by Name, CNIC, or Subject..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-10">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <!-- Teacher Search Results Dropdown -->
                    <div id="teacherSearchResults" class="hidden absolute z-10 w-full bg-white mt-1 rounded-md shadow-lg max-h-60 overflow-y-auto border border-gray-200"></div>
                </div>
            </div>
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
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($books)): ?>
                    <tr><td colspan="7" class="p-4 text-center text-gray-500">No books in inventory.</td></tr>
                <?php else: ?>
                    <?php 
                    // Sort books by class then subject
                    usort($books, function($a, $b) {
                        return strcmp($a['class'], $b['class']) ?: strcmp($a['subject'], $b['subject']);
                    });
                    foreach ($books as $book):
                        // Skip deleted books
                        if (isset($book['status']) && $book['status'] == 'deleted') continue;
                        
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
                        <td class="p-4 text-center">
                            <div class="flex gap-2 justify-center">
                                <?php if ($book['qty_available'] > 0): ?>
                                <button onclick="openDamagedModal('<?php echo $book['id']; ?>', '<?php echo htmlspecialchars(addslashes($book['name'])); ?>', <?php echo $book['qty_available']; ?>)" 
                                        class="px-2 py-1 bg-orange-100 text-orange-600 rounded text-xs hover:bg-orange-200 transition-colors" 
                                        title="Move to Damaged">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                                <?php endif; ?>
                                <button onclick="confirmDelete('<?php echo $book['id']; ?>', '<?php echo htmlspecialchars(addslashes($book['name'])); ?>')" 
                                        class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs hover:bg-red-200 transition-colors" 
                                        title="Delete Book">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
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

<!-- Delete Book Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <form method="POST" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-trash text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Delete Book</h3>
                </div>
                <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <input type="hidden" name="book_id" id="deleteBookId">
            
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    This will mark the book as deleted. The book will no longer appear in inventory but history will be preserved.
                </p>
            </div>
            
            <p class="text-gray-700 mb-4">
                Are you sure you want to delete <strong id="deleteBookName" class="text-gray-900"></strong>?
            </p>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" name="delete_book" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <i class="fas fa-trash"></i> Delete Book
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Move to Damaged Modal -->
<div id="damagedModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <form method="POST" class="p-6" id="damagedForm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Move to Damaged</h3>
                </div>
                <button type="button" onclick="closeDamagedModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <input type="hidden" name="book_id" id="damagedBookId">
            
            <p class="text-gray-700 mb-4">
                Book: <strong id="damagedBookName" class="text-gray-900"></strong>
            </p>
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                <p class="text-sm text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Damaged books will be moved to damaged inventory and removed from available stock permanently.
                </p>
            </div>
            
            <div class="space-y-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Quantity to Move <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" id="damagedQuantity" min="1" required 
                           class="w-full border border-gray-300 rounded-lg p-2">
                    <p class="text-xs text-gray-500 mt-1">Available: <span id="maxQuantity"></span></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Damage Type <span class="text-red-500">*</span>
                    </label>
                    <select name="damage_type" required class="w-full border border-gray-300 rounded-lg p-2">
                        <option value="">-- Select Damage Type --</option>
                        <option value="torn_pages">Torn Pages</option>
                        <option value="water_damage">Water Damage</option>
                        <option value="missing_pages">Missing Pages</option>
                        <option value="cover_damage">Cover Damage</option>
                        <option value="writing_scribbles">Writing/Scribbles</option>
                        <option value="binding_broken">Binding Broken</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Remarks <span class="text-red-500">*</span>
                    </label>
                    <textarea name="remarks" rows="2" required 
                              class="w-full border border-gray-300 rounded-lg p-2" 
                              placeholder="Describe the damage or reason..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeDamagedModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" name="move_to_damaged" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> Move to Damaged
                </button>
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
    if (!document.getElementById('teacherSearch').contains(e.target)) {
        document.getElementById('teacherSearchResults').classList.add('hidden');
    }
});

// Tab Switching Function
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active styling from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-teal-600', 'border-b-2', 'border-teal-600', 'text-blue-600', 'border-blue-600', 'text-purple-600', 'border-purple-600');
        btn.classList.add('text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active styling to selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.remove('text-gray-500');
    
    if (tabName === 'individual') {
        activeTab.classList.add('text-teal-600', 'border-b-2', 'border-teal-600');
    } else if (tabName === 'teacher') {
        activeTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
    } else if (tabName === 'bulk') {
        activeTab.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
    }
}

// Teacher Search Functionality
document.getElementById('teacherSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    const resultsDiv = document.getElementById('teacherSearchResults');
    
    console.log('Teacher search query:', query);
    
    if (query.length < 2) {
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
        return;
    }
    
    fetch(`../api/search_teachers.php?query=${encodeURIComponent(query)}`)
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Teacher search results:', data);
            resultsDiv.innerHTML = '';
            if (data.length > 0) {
                resultsDiv.classList.remove('hidden');
                data.forEach(teacher => {
                    const div = document.createElement('div');
                    div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0';
                    div.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-gray-800">${teacher.name}</p>
                                <p class="text-xs text-gray-500">CNIC: ${teacher.cnic} | Subject: ${teacher.subject || 'N/A'}</p>
                            </div>
                            <span class="text-blue-600"><i class="fas fa-chevron-right"></i></span>
                        </div>
                    `;
                    div.onclick = () => {
                        window.location.href = `book_bank_teacher_actions.php?teacher_id=${teacher.id}`;
                    };
                    resultsDiv.appendChild(div);
                });
            } else {
                resultsDiv.classList.remove('hidden');
                resultsDiv.innerHTML = '<div class="p-3 text-gray-500 text-sm">No teachers found</div>';
            }
        })
        .catch(err => {
            console.error('Teacher search error:', err);
            resultsDiv.classList.remove('hidden');
            resultsDiv.innerHTML = '<div class="p-3 text-red-500 text-sm">Error searching teachers. Check console for details.</div>';
        });
});

// Delete Book Functions
function confirmDelete(bookId, bookName) {
    document.getElementById('deleteBookId').value = bookId;
    document.getElementById('deleteBookName').textContent = bookName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Move to Damaged Functions
function openDamagedModal(bookId, bookName, maxQty) {
    document.getElementById('damagedBookId').value = bookId;
    document.getElementById('damagedBookName').textContent = bookName;
    document.getElementById('maxQuantity').textContent = maxQty;
    document.getElementById('damagedQuantity').max = maxQty;
    document.getElementById('damagedQuantity').value = Math.min(1, maxQty);
    document.getElementById('damagedModal').classList.remove('hidden');
}

function closeDamagedModal() {
    document.getElementById('damagedModal').classList.add('hidden');
    document.getElementById('damagedForm').reset();
}

// Validate damaged quantity
if (document.getElementById('damagedForm')) {
    document.getElementById('damagedForm').addEventListener('submit', function(e) {
        const qty = parseInt(document.getElementById('damagedQuantity').value);
        const max = parseInt(document.getElementById('damagedQuantity').max);
        
        if (qty > max) {
            e.preventDefault();
            alert(`Quantity cannot exceed ${max} (available stock)`);
            return false;
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
