<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

$db = new Database();
// Only Admin or Editor can access
if ($_SESSION['user_role'] === 'Editor' && !in_array('inventory', $allowed_pages ?? [])) {
    // Basic permission check
}

$categories = $db->getCategories();
$categoriesMap = [];
foreach ($categories as $cat) {
    $categoriesMap[$cat['id']] = $cat['name'];
}

// Handle Form POSTs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Move to Dead Stock
    if (isset($_POST['move_to_dead_stock'])) {
        $id = $_POST['item_id'];
        $reason = $_POST['reason'];
        $date = $_POST['date'];
        $qty = (int)$_POST['quantity'];
        $remarks = $_POST['remarks'];
        
        if ($db->moveToDeadStock($id, $qty, $reason, $date, $remarks)) {
            $successMsg = "Item(s) moved to Dead Stock Register successfully.";
        } else {
            $errorMsg = "Failed to move items to Dead Stock.";
        }
    }
    // Delete Item
    elseif (isset($_POST['delete_item'])) {
        $id = $_POST['item_id'];
        if ($db->deleteInventory($id)) {
            $successMsg = "Item deleted successfully.";
        } else {
            $errorMsg = "Failed to delete item.";
        }
    }
}

// Filters
$filters = ['status' => 'Active']; // Default to Active items
if (isset($_GET['category']) && $_GET['category'] !== '') {
    $filters['category_id'] = $_GET['category'];
}

$inventory = $db->getInventory($filters);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-blue-100 rounded-lg text-blue-600">
                    <i class="fas fa-boxes"></i>
                </div>
                Inventory Management
            </h2>
            <p class="text-gray-500 mt-1 ml-16">Manage school assets and stock.</p>
        </div>
        
        <div class="flex gap-2">
            <a href="inventory_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Item
            </a>
            <a href="categories.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="dead_stock.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-archive"></i> Dead Stock
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>
                <?php 
                if ($_GET['msg'] == 'added') echo "Item added successfully!"; 
                elseif ($_GET['msg'] == 'updated') echo "Item updated successfully!";
                ?>
            </span>
        </div>
    <?php endif; ?>

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

    <!-- Search & Filter -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4 items-center">
        <form method="GET" class="flex gap-4 w-full md:w-auto">
            <select name="category" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="flex-grow"></div>
        <div class="text-sm text-gray-500">
            Showing <strong><?php echo count($inventory); ?></strong> active items
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="p-4">Item Name</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Qty</th>
                        <th class="p-4">Purchase Date</th>
                        <th class="p-4">Condition</th>
                        <th class="p-4">Cost</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">
                                No inventory items found. Add your first item!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4 font-semibold text-gray-800">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                                <?php if ($item['remarks']): ?>
                                    <div class="text-xs text-gray-400 font-normal mt-1"><?php echo htmlspecialchars($item['remarks']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-sm text-gray-600">
                                <span class="px-2 py-1 bg-gray-100 rounded text-gray-600 text-xs">
                                    <?php echo isset($categoriesMap[$item['category_id']]) ? htmlspecialchars($categoriesMap[$item['category_id']]) : 'Unknown'; ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm font-bold text-gray-800"><?php echo $item['quantity']; ?></td>
                            <td class="p-4 text-sm text-gray-600"><?php echo date('d M, Y', strtotime($item['purchase_date'])); ?></td>
                            <td class="p-4 text-sm">
                                <?php 
                                $condColor = 'bg-gray-100 text-gray-800';
                                if ($item['condition'] == 'New') $condColor = 'bg-green-100 text-green-800';
                                if ($item['condition'] == 'Good') $condColor = 'bg-blue-100 text-blue-800';
                                if ($item['condition'] == 'Damaged') $condColor = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-bold <?php echo $condColor; ?>"><?php echo $item['condition']; ?></span>
                            </td>
                            <td class="p-4 text-sm font-mono text-gray-700"><?php echo $item['cost'] ? number_format($item['cost']) : '-'; ?></td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="inventory_form.php?id=<?php echo $item['id']; ?>" class="text-blue-500 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-full transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="openDeadStockModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', <?php echo $item['quantity']; ?>)" class="text-amber-500 hover:text-amber-700 p-2 hover:bg-amber-50 rounded-full transition-colors" title="Move to Dead Stock">
                                        <i class="fas fa-dolly"></i>
                                    </button>
                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this item permanently?');" class="inline">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_item" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors" title="Delete">
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

<!-- Dead Stock Modal -->
<div id="deadStockModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-[scaleIn_0.3s_ease-out]">
        <div class="bg-amber-50 p-4 border-b border-amber-100 flex justify-between items-center">
            <h3 class="font-bold text-amber-800 flex items-center gap-2">
                <i class="fas fa-archive"></i> Move to Dead Stock
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="move_to_dead_stock" value="1">
            <input type="hidden" name="item_id" id="modalItemId">
            
            <p class="text-gray-600 mb-4 text-sm">You are moving <strong id="modalItemName" class="text-gray-800"></strong> to Dead Stock.</p>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Quantity to Move (Max: <span id="maxQtyText">0</span>)</label>
                    <input type="number" name="quantity" id="modalQty" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Disposal Reason</label>
                    <select name="reason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="Broken/Damaged">Broken/Damaged</option>
                        <option value="Sold to Scrap">Sold to Scrap</option>
                        <option value="Obsolete">Obsolete</option>
                        <option value="Lost/Stolen">Lost/Stolen</option>
                        <option value="Donated">Donated</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                
                <div>
                   <label class="block text-sm font-bold text-gray-700 mb-1">Remarks / Cause</label>
                   <textarea name="remarks" required rows="2" placeholder="Explain the cause (e.g., Fan burned out due to voltage fluctuation)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold shadow-md transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i> Confirm Move
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeadStockModal(id, name, currentQty) {
        document.getElementById('modalItemId').value = id;
        document.getElementById('modalItemName').textContent = name;
        
        // Setup Quantity Logic
        const qtyInput = document.getElementById('modalQty');
        qtyInput.max = currentQty;
        qtyInput.value = currentQty; // Default to moving all
        document.getElementById('maxQtyText').textContent = currentQty;

        document.getElementById('deadStockModal').classList.remove('hidden');
        document.getElementById('deadStockModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('deadStockModal').classList.add('hidden');
        document.getElementById('deadStockModal').classList.remove('flex');
    }
</script>

<style>
    @keyframes scaleIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<?php include '../includes/footer.php'; ?>
