<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();

$successMsg = '';
$errorMsg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        $name = trim($_POST['class_name']);
        if (!empty($name)) {
            if ($db->addClass($name)) {
                $successMsg = "Class added successfully!";
            } else {
                $errorMsg = "Failed to add class.";
            }
        } else {
            $errorMsg = "Class Name is required.";
        }
    } elseif (isset($_POST['update_class'])) {
        $id = $_POST['class_id'];
        $name = trim($_POST['class_name']);
        $sortOrder = (int)$_POST['sort_order'];
        
        if (!empty($name)) {
            $classes = $db->getClasses();
            foreach ($classes as &$c) {
                if ($c['id'] == $id) {
                    $c['class_name'] = $name;
                    $c['sort_order'] = $sortOrder;
                    break;
                }
            }
            if ($db->updateClasses($classes)) {
                $successMsg = "Class updated successfully!";
                echo "<script>window.location.href = 'manage_classes.php?msg=updated';</script>";
                exit;
            } else {
                $errorMsg = "Failed to update class.";
            }
        }
    } elseif (isset($_POST['delete_class'])) {
        $id = $_POST['class_id'];
        if ($db->deleteClass($id)) {
            $successMsg = "Class deleted successfully!";
        } else {
            $errorMsg = "Failed to delete class.";
        }
    }
}

$classes = $db->getClasses();

// Handle Edit Mode
$editMode = false;
$editClass = ['class_name' => '', 'id' => '', 'sort_order' => ''];

if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    foreach ($classes as $c) {
        if ($c['id'] == $editId) {
            $editMode = true;
            $editClass = $c;
            break;
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $successMsg = "Class updated successfully!";
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-indigo-100 rounded-lg text-indigo-600">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                Manage School Classes
            </h2>
            <div class="flex gap-2">
                <a href="students.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                    <i class="fas fa-users"></i> Students List
                </a>
            </div>
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
            <!-- Add/Edit Class Form -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">
                        <?php echo $editMode ? 'Edit Class' : 'Add New Class'; ?>
                    </h3>
                    <form action="" method="POST" class="space-y-4">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="class_id" value="<?php echo $editClass['id']; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class Name</label>
                            <input type="text" name="class_name" required placeholder="e.g. Class 6" value="<?php echo htmlspecialchars($editClass['class_name']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                            <input type="number" name="sort_order" required value="<?php echo $editClass['sort_order'] ?: (count($classes) + 1); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <small class="text-gray-500">Determines sequence in dropdowns</small>
                        </div>
                        
                        <?php if ($editMode): ?>
                            <div class="flex gap-2">
                                <button type="submit" name="update_class" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <a href="manage_classes.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                    Cancel
                                </a>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="add_class" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Add Class
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Classes List -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                                    <th class="p-4 w-20">Order</th>
                                    <th class="p-4">Class Name</th>
                                    <th class="p-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($classes)): ?>
                                    <tr>
                                        <td colspan="3" class="p-8 text-center text-gray-500">
                                            No classes found. Create one to get started!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($classes as $c): ?>
                                    <tr class="hover:bg-gray-50 transition-colors group <?php echo ($editMode && $c['id'] == $editId) ? 'bg-indigo-50' : ''; ?>">
                                        <td class="p-4 text-gray-500 font-mono"><?php echo $c['sort_order']; ?></td>
                                        <td class="p-4 font-semibold text-gray-800"><?php echo htmlspecialchars($c['class_name']); ?></td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="manage_classes.php?edit=<?php echo $c['id']; ?>" class="text-blue-400 hover:text-blue-600 transition-colors p-2 rounded-full hover:bg-blue-50" title="Edit Class">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="" method="POST" onsubmit="return confirm('Are you sure? Removing a class may affect student filtering.');" class="inline">
                                                    <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" name="delete_class" class="text-red-400 hover:text-red-600 transition-colors p-2 rounded-full hover:bg-red-50" title="Delete Class">
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
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100 text-blue-800 text-sm">
                    <i class="fas fa-info-circle mr-2"></i> 
                    Classes created here will automatically appear in all dropdowns across the system (Admission, Results, Reports, etc).
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
