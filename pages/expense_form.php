<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();
$categories = $db->getExpenseCategories();
$expense = null;
$heading = "Add New Expense";
$btnText = "Add Expense";
$action = "add";

if (isset($_GET['id'])) {
    $expense = $db->getExpense($_GET['id']);
    if ($expense) {
        $heading = "Edit Expense";
        $btnText = "Update Expense";
        $action = "edit";
    }
}

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'category_id' => $_POST['category_id'],
        'description' => $_POST['description'],
        'amount' => $_POST['amount'],
        'expense_date' => $_POST['expense_date'],
        'payment_method' => $_POST['payment_method'],
        'vendor' => $_POST['vendor'],
        'receipt_ref' => $_POST['receipt_ref'],
        'notes' => $_POST['notes']
    ];

    if ($_POST['action'] === 'add') {
        if ($db->addExpense($data)) {
            echo "<script>window.location.href = 'expenses.php?msg=added';</script>";
            exit;
        } else {
            $errorMsg = "Failed to add expense.";
        }
    } elseif ($_POST['action'] === 'edit') {
        if ($db->updateExpense($_POST['id'], $data)) {
            echo "<script>window.location.href = 'expenses.php?msg=updated';</script>";
            exit;
        } else {
            $errorMsg = "Failed to update expense.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-red-100 rounded-lg text-red-600">
                    <i class="fas fa-receipt"></i>
                </div>
                <?php echo $heading; ?>
            </h2>
            <a href="expenses.php" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2">
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
                <?php if ($expense): ?>
                    <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Expense Category <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <select name="category_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($expense && $expense['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="expense_categories.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-3 rounded-lg flex items-center justify-center transition-colors" title="Manage Categories">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Amount (Rs.) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" required step="0.01" min="0" value="<?php echo $expense ? $expense['amount'] : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="0.00">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Description <span class="text-red-500">*</span></label>
                        <input type="text" name="description" required value="<?php echo $expense ? htmlspecialchars($expense['description']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="e.g. Monthly electricity bill for July">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Expense Date <span class="text-red-500">*</span></label>
                        <input type="date" name="expense_date" required value="<?php echo $expense ? $expense['expense_date'] : date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Payment Method</label>
                        <select name="payment_method" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                            <?php 
                            $methods = ['Cash', 'Bank Transfer', 'Cheque', 'Credit Card', 'Online Payment', 'Other']; 
                            foreach($methods as $method):
                            ?>
                                <option value="<?php echo $method; ?>" <?php echo ($expense && $expense['payment_method'] === $method) ? 'selected' : ''; ?>><?php echo $method; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Vendor / Payee</label>
                        <input type="text" name="vendor" value="<?php echo $expense ? htmlspecialchars($expense['vendor']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="e.g. K-Electric, Stationery Shop">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Receipt / Reference #</label>
                        <input type="text" name="receipt_ref" value="<?php echo $expense ? htmlspecialchars($expense['receipt_ref']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="e.g. INV-001, Bill Ref #">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Any additional notes..."><?php echo $expense ? htmlspecialchars($expense['notes']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="pt-6 border-t flex justify-end gap-3">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg transition-colors shadow-md flex items-center gap-2">
                        <i class="fas fa-save"></i> <?php echo $btnText; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
