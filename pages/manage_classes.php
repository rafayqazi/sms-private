<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check permissions
if (!canAccessPage(basename(__FILE__))) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/header.php';

$db = new Database();

$successMsg = '';
$errorMsg = '';

$isTeacher = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isTeacher) {
        die("Unauthorized logic.");
    }
    // CSRF Verification
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    
    if (isset($_POST['add_class'])) {
        $name = trim($_POST['class_name']);
        $grRequired = 1; // Mandatory for all classes by default
        $stage = $_POST['stage'] ?? 'Elementary';
        $hasGroup = ($stage === 'College' && isset($_POST['has_group'])) ? 1 : 0;
        $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : null;
        if (!empty($name)) {
            if ($db->addClass($name, $grRequired, $hasGroup, $stage, $sortOrder)) {
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
            $grRequired = 1; // Mandatory for all classes by default
            $stage = $_POST['stage'] ?? 'Elementary';
            $hasGroup = ($stage === 'College' && isset($_POST['has_group'])) ? 1 : 0;
            foreach ($classes as $i => $c) {
                if ($c['id'] == $id) {
                    $classes[$i]['class_name'] = $name;
                    $classes[$i]['sort_order'] = $sortOrder;
                    $classes[$i]['is_gr_required'] = $grRequired;
                    $classes[$i]['has_group'] = $hasGroup;
                    $classes[$i]['stage'] = $stage;
                    break;
                }
            }
            if ($db->updateClasses($classes, $id, $sortOrder)) {
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

// Define stages
$stages = ['Pre-Primary', 'Elementary', 'College'];

// Group classes by stage and calculate next available order
$groupedClasses = [];
$nextOrders = [];
foreach ($stages as $s) {
    $groupedClasses[$s] = [];
    $nextOrders[$s] = 1;
}

foreach ($classes as $c) {
    $s = $c['stage'] ?? 'Elementary';
    if (!isset($groupedClasses[$s])) $groupedClasses[$s] = [];
    $groupedClasses[$s][] = $c;
    
    // Track max order for each stage
    if ($c['sort_order'] >= $nextOrders[$s]) {
        $nextOrders[$s] = $c['sort_order'] + 1;
    }
}

// Handle Edit Mode
$editMode = false;
$editClass = ['class_name' => '', 'id' => '', 'sort_order' => '', 'stage' => 'Elementary'];

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

        <div class="grid grid-cols-1 <?php echo $isTeacher ? 'md:grid-cols-1' : 'md:grid-cols-3'; ?> gap-8">
            <?php if (!$isTeacher): ?>
            <!-- Add/Edit Class Form -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">
                        <?php echo $editMode ? 'Edit Class' : 'Add New Class'; ?>
                    </h3>
                    <form action="" method="POST" class="space-y-4">
                        <?php echo csrfInput(); ?>
                        <?php if ($editMode): ?>
                            <input type="hidden" name="class_id" value="<?php echo $editClass['id']; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class Name</label>
                            <input type="text" name="class_name" id="class_name" required placeholder="e.g. Class 6" value="<?php echo htmlspecialchars($editClass['class_name']); ?>"
                                oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">School Stage</label>
                            <select name="stage" id="stageSelect" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <?php foreach ($stages as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($editClass['stage'] == $s) ? 'selected' : ''; ?>>
                                        <?php echo $s; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                            <input type="number" name="sort_order" id="sortOrderInput" required value="<?php echo $editClass['sort_order'] ?: ($nextOrders[$editClass['stage'] ?? 'Elementary']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <small class="text-gray-500">Determines sequence in dropdowns</small>
                        </div>


                        <div id="groupContainer" class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200 <?php echo $editClass['stage'] === 'College' ? '' : 'hidden'; ?>">
                            <input type="checkbox" name="has_group" id="has_group" <?php echo ($editMode ? (isset($editClass['has_group']) && $editClass['has_group'] ? 'checked' : '') : ''); ?> 
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="has_group" class="text-sm font-medium text-gray-700 cursor-pointer">Group (Pre-Med/Eng)?</label>
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
            <?php endif; ?>

            <!-- Classes List -->
            <div class="<?php echo $isTeacher ? 'md:col-span-1' : 'md:col-span-2'; ?>">
                <div class="space-y-8">
                    <?php foreach ($groupedClasses as $stageName => $stageClasses): ?>
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h4 class="text-sm font-black text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                                <i class="fas <?php 
                                    echo $stageName === 'Pre-Primary' ? 'fa-child' : 
                                        ($stageName === 'Elementary' ? 'fa-school' : 'fa-university'); 
                                ?>"></i>
                                <?php echo $stageName; ?> Section
                            </h4>
                            <span class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-2 py-0.5 rounded-full uppercase">
                                <?php echo count($stageClasses); ?> Classes
                            </span>
                        </div>
                        <div class="overflow-x-auto <?php echo $stageName !== 'College' ? 'max-w-2xl' : ''; ?>">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 border-b border-gray-200 text-[10px] uppercase tracking-wider text-gray-400 font-bold">
                                        <th class="p-4 w-20 text-center">Order</th>
                                        <th class="p-4">Class Name</th>
                                        <?php if ($stageName === 'College'): ?>
                                        <th class="p-4 text-center">Group</th>
                                        <?php endif; ?>
                                        <?php if (!$isTeacher): ?>
                                        <th class="p-4 w-32 text-center">Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (empty($stageClasses)): ?>
                                        <tr>
                                            <td colspan="5" class="p-8 text-center text-gray-400 text-sm italic">
                                                No classes added in this section yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($stageClasses as $c): ?>
                                        <tr class="hover:bg-gray-50 transition-colors group <?php echo ($editMode && $c['id'] == $editId) ? 'bg-indigo-50' : ''; ?>">
                                            <td class="p-4 text-center text-gray-400 font-mono text-xs"><?php echo $c['sort_order']; ?></td>
                                            <td class="p-4 font-bold text-gray-800"><?php echo htmlspecialchars($c['class_name']); ?></td>
                                            <?php if ($stageName === 'College'): ?>
                                            <td class="p-4 text-center">
                                                <?php if (isset($c['has_group']) && $c['has_group']): ?>
                                                    <span class="text-blue-500" title="Has Group"><i class="fas fa-layer-group"></i></span>
                                                <?php else: ?>
                                                    <span class="text-gray-300"><i class="fas fa-minus"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                            <?php if (!$isTeacher): ?>
                                            <td class="p-4 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <a href="manage_classes.php?edit=<?php echo $c['id']; ?>" class="text-gray-400 hover:text-indigo-600 transition-colors p-2 rounded-lg hover:bg-indigo-50" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure? Removing a class may affect student filtering.');" class="inline">
                                                        <?php echo csrfInput(); ?>
                                                        <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                                        <button type="submit" name="delete_class" class="text-gray-400 hover:text-red-600 transition-colors p-2 rounded-lg hover:bg-red-50" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100 text-blue-800 text-sm">
                    <i class="fas fa-info-circle mr-2"></i> 
                    Classes created here will automatically appear in all dropdowns across the system (Admission, Results, Reports, etc).
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Stage-based field visibility and auto-sort order
const nextOrders = <?php echo json_encode($nextOrders); ?>;
const isEditMode = <?php echo $editMode ? 'true' : 'false'; ?>;

document.getElementById('stageSelect').addEventListener('change', function() {
    const stage = this.value;
    const groupContainer = document.getElementById('groupContainer');
    
    // Toggle group visibility
    if (stage === 'College') {
        groupContainer.classList.remove('hidden');
    } else {
        groupContainer.classList.add('hidden');
        document.getElementById('has_group').checked = false;
    }

    // Update sort order automatically if in Add Mode
    if (!isEditMode) {
        document.getElementById('sortOrderInput').value = nextOrders[stage] || 1;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
