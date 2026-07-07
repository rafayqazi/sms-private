<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();

// Handle Form POSTs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Category
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        if ($db->addExpenseCategory($name, $description)) {
            $successMsg = "Category added successfully.";
        } else {
            $errorMsg = "Failed to add category.";
        }
    }
    // Delete Category
    elseif (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        if ($db->deleteExpenseCategory($id)) {
            $successMsg = "Category deleted successfully.";
        } else {
            $errorMsg = "Failed to delete category.";
        }
    }
    // Update Category
    elseif (isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        if ($db->updateExpenseCategory($id, $name, $description)) {
            $successMsg = "Category updated successfully.";
        } else {
            $errorMsg = "Failed to update category.";
        }
    }
}

$categories = $db->getExpenseCategories();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-indigo-100 rounded-lg text-indigo-600">
                    <i class="fas fa-tags"></i>
                </div>
                Expense Categories
            </h2>
            <p class="text-gray-500 mt-1 ml-16">Manage expense categories for your school.</p>
        </div>
        <a href="expenses.php" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Expenses
        </a>
    </div>

    <?php if (isset($successMsg)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $successMsg; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMsg)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $errorMsg; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add Category Form -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle text-indigo-600"></i> Add New Category
            </h3>
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Electricity Bill"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Brief description..."></textarea>
                </div>
                <button type="submit" name="add_category" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Add Category
                </button>
            </form>
        </div>

        <!-- Categories List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                                <th class="p-4">#</th>
                                <th class="p-4">Category Name</th>
                                <th class="p-4">Description</th>
                                <th class="p-4">Created</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-500">
                                        No categories found. Add your first category!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $i = 1; ?>
                                <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4 text-sm text-gray-500"><?php echo $i++; ?></td>
                                    <td class="p-4 font-semibold text-gray-800"><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td class="p-4 text-sm text-gray-600"><?php echo htmlspecialchars($cat['description'] ?: '-'); ?></td>
                                    <td class="p-4 text-sm text-gray-500"><?php echo date('d M, Y', strtotime($cat['created_at'])); ?></td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo addslashes($cat['description']); ?>')" 
                                                class="text-blue-500 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-full transition-colors" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="" method="POST" onsubmit="return confirm('Delete this category?');" class="inline">
                                                <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" name="delete_category" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-[scaleIn_0.3s_ease-out]">
        <div class="bg-indigo-50 p-4 border-b border-indigo-100 flex justify-between items-center">
            <h3 class="font-bold text-indigo-800 flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Category
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="update_category" value="1">
            <input type="hidden" name="category_id" id="editCategoryId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Category Name</label>
                    <input type="text" name="name" id="editCategoryName" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="editCategoryDesc" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes scaleIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<script>
    function editCategory(id, name, description) {
        document.getElementById('editCategoryId').value = id;
        document.getElementById('editCategoryName').value = name;
        document.getElementById('editCategoryDesc').value = description;
        document.getElementById('editCategoryModal').classList.remove('hidden');
        document.getElementById('editCategoryModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editCategoryModal').classList.add('hidden');
        document.getElementById('editCategoryModal').classList.remove('flex');
    }
</script>

<?php include '../includes/footer.php'; ?>
