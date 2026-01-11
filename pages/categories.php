<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();
// Only Admin or Editor can access
if ($_SESSION['user_role'] === 'Editor' && !in_array('inventory', $allowed_pages ?? [])) {
    // Basic permission check - for now assume Admins can access
}

$successMsg = '';
$errorMsg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (!empty($name)) {
            if ($db->addCategory($name, $description)) {
                $successMsg = "Category added successfully!";
            } else {
                $errorMsg = "Failed to add category.";
            }
        } else {
            $errorMsg = "Category Name is required.";
        }
    } elseif (isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (!empty($name)) {
            if ($db->updateCategory($id, $name, $description)) {
                $successMsg = "Category updated successfully!";
                // Redirect to clear edit query param
                echo "<script>window.location.href = 'categories.php?msg=updated';</script>";
                exit; 
            } else {
                $errorMsg = "Failed to update category.";
            }
        } else {
            $errorMsg = "Category Name is required.";
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        if ($db->deleteCategory($id)) {
            $successMsg = "Category deleted successfully!";
        } else {
            $errorMsg = "Failed to delete category.";
        }
    }
}

$categories = $db->getCategories();

// Handle Edit Mode
$editMode = false;
$editCategory = ['name' => '', 'description' => '', 'id' => ''];

if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    foreach ($categories as $cat) {
        if ($cat['id'] == $editId) {
            $editMode = true;
            $editCategory = $cat;
            break;
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $successMsg = "Category updated successfully!";
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-indigo-100 rounded-lg text-indigo-600">
                    <i class="fas fa-tags"></i>
                </div>
                Inventory Categories
            </h2>
            <a href="inventory.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>
        </div>

        <?php if ($successMsg): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $successMsg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $errorMsg; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Add/Edit Category Form -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">
                        <?php echo $editMode ? 'Edit Category' : 'Add New Category'; ?>
                    </h3>
                    <form action="" method="POST" class="space-y-4">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                            <input type="text" name="name" required placeholder="e.g. Electronic Items" value="<?php echo htmlspecialchars($editCategory['name']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                            <textarea name="description" rows="3" placeholder="Brief description..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($editCategory['description']); ?></textarea>
                        </div>
                        
                        <?php if ($editMode): ?>
                            <div class="flex gap-2">
                                <button type="submit" name="update_category" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <a href="categories.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                    Cancel
                                </a>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="add_category" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                                    <th class="p-4">Name</th>
                                    <th class="p-4">Description</th>
                                    <th class="p-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="3" class="p-8 text-center text-gray-500">
                                            No categories found. Create one to get started!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr class="hover:bg-gray-50 transition-colors group <?php echo ($editMode && $cat['id'] == $editId) ? 'bg-indigo-50' : ''; ?>">
                                        <td class="p-4 font-semibold text-gray-800"><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td class="p-4 text-gray-600 text-sm"><?php echo htmlspecialchars($cat['description']); ?></td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="text-blue-400 hover:text-blue-600 transition-colors p-2 rounded-full hover:bg-blue-50" title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="" method="POST" onsubmit="return confirm('Are you sure? This might affect items assigned to this category.');" class="inline">
                                                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                    <button type="submit" name="delete_category" class="text-red-400 hover:text-red-600 transition-colors p-2 rounded-full hover:bg-red-50" title="Delete Category">
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
</div>

<?php include '../includes/footer.php'; ?>
