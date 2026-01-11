<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();
$categories = $db->getCategories();
$item = null;
$heading = "Add New Item";
$btnText = "Add Item";
$action = "add";

if (isset($_GET['id'])) {
    $item = $db->getInventoryItem($_GET['id']);
    if ($item) {
        $heading = "Edit Item";
        $btnText = "Update Item";
        $action = "edit";
    }
}

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'item_name' => $_POST['item_name'],
        'category_id' => $_POST['category_id'],
        'quantity' => $_POST['quantity'],
        'cost' => $_POST['cost'],
        'purchase_date' => $_POST['purchase_date'],
        'condition' => $_POST['condition'],
        'remarks' => $_POST['remarks']
    ];

    if ($_POST['action'] === 'add') {
        if ($db->addInventory($data)) {
            echo "<script>window.location.href = 'inventory.php?msg=added';</script>";
            exit;
        } else {
            $errorMsg = "Failed to add item.";
        }
    } elseif ($_POST['action'] === 'edit') {
        if ($db->updateInventory($_POST['id'], $data)) {
            echo "<script>window.location.href = 'inventory.php?msg=updated';</script>";
            exit;
        } else {
            $errorMsg = "Failed to update item.";
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-blue-100 rounded-lg text-blue-600">
                    <i class="fas fa-box-open"></i>
                </div>
                <?php echo $heading; ?>
            </h2>
            <a href="inventory.php" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>

        <?php if ($errorMsg): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($item): ?>
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Item Name</label>
                        <input type="text" name="item_name" required value="<?php echo $item ? htmlspecialchars($item['item_name']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g. Ceiling Fan (Pak Fan)">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Category</label>
                        <div class="flex gap-2">
                            <select name="category_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($item && $item['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="categories.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-3 rounded-lg flex items-center justify-center transition-colors" title="Manage Categories">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Quantity</label>
                        <input type="number" name="quantity" required min="1" value="<?php echo $item ? $item['quantity'] : '1'; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" required value="<?php echo $item ? $item['purchase_date'] : date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Cost (Total or Unit)</label>
                        <input type="number" name="cost" step="0.01" value="<?php echo $item ? $item['cost'] : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0.00">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Condition / Status</label>
                        <select name="condition" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <?php 
                            $conditions = ['New', 'Good', 'Fair', 'Repairable', 'Damaged']; 
                            foreach($conditions as $cond):
                            ?>
                                <option value="<?php echo $cond; ?>" <?php echo ($item && $item['condition'] === $cond) ? 'selected' : ''; ?>><?php echo $cond; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Remarks</label>
                        <textarea name="remarks" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo $item ? htmlspecialchars($item['remarks']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="pt-6 border-t flex justify-end gap-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-colors shadow-md flex items-center gap-2">
                        <i class="fas fa-save"></i> <?php echo $btnText; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
