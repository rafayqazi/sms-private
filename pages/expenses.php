<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();

$categories = $db->getExpenseCategories();
$categoriesMap = [];
foreach ($categories as $cat) {
    $categoriesMap[$cat['id']] = $cat['name'];
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expense'])) {
    $id = $_POST['expense_id'];
    if ($db->deleteExpense($id)) {
        $successMsg = "Expense deleted successfully.";
    } else {
        $errorMsg = "Failed to delete expense.";
    }
}

// Filters
$filters = [];
if (isset($_GET['category']) && $_GET['category'] !== '') {
    $filters['category_id'] = $_GET['category'];
}
if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
    $filters['date_from'] = $_GET['date_from'];
}
if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
    $filters['date_to'] = $_GET['date_to'];
}

$expenses = $db->getExpenses($filters);

// Calculate total
$totalAmount = array_sum(array_column($expenses, 'amount'));

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-3 bg-red-100 rounded-lg text-red-600">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                Expense Management
            </h2>
            <p class="text-gray-500 mt-1 ml-16">Track all school expenses and payments.</p>
        </div>
        
        <div class="flex gap-2">
            <a href="expense_form.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Expense
            </a>
            <a href="expense_categories.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-tags"></i> Categories
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>
                <?php 
                if ($_GET['msg'] == 'added') echo "Expense added successfully!";
                elseif ($_GET['msg'] == 'updated') echo "Expense updated successfully!";
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-list text-red-600 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 font-medium">Total Expenses</div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo count($expenses); ?></div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-calculator text-green-600 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 font-medium">Total Amount</div>
                    <div class="text-2xl font-bold text-gray-800">Rs. <?php echo number_format($totalAmount); ?></div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 font-medium">Categories</div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo count($categories); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-auto">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-gray-700 w-full">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-full md:w-auto">
                <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div class="w-full md:w-auto">
                <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="expenses.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="p-4">#</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Description</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Payment Method</th>
                        <th class="p-4">Vendor/Payee</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">
                                <i class="fas fa-receipt text-4xl text-gray-300 mb-3 block"></i>
                                No expenses found. Add your first expense!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; ?>
                        <?php foreach ($expenses as $exp): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4 text-sm text-gray-500"><?php echo $i++; ?></td>
                            <td class="p-4 text-sm text-gray-700 font-medium"><?php echo date('d M, Y', strtotime($exp['expense_date'])); ?></td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-bold">
                                    <?php echo isset($categoriesMap[$exp['category_id']]) ? htmlspecialchars($categoriesMap[$exp['category_id']]) : 'Unknown'; ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm text-gray-800 font-medium"><?php echo htmlspecialchars($exp['description']); ?></td>
                            <td class="p-4 text-sm font-mono font-bold text-red-600">Rs. <?php echo number_format($exp['amount']); ?></td>
                            <td class="p-4 text-sm text-gray-600">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs"><?php echo htmlspecialchars($exp['payment_method']); ?></span>
                            </td>
                            <td class="p-4 text-sm text-gray-600"><?php echo htmlspecialchars($exp['vendor'] ?: '-'); ?></td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="expense_form.php?id=<?php echo $exp['id']; ?>" class="text-blue-500 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-full transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');" class="inline">
                                        <input type="hidden" name="expense_id" value="<?php echo $exp['id']; ?>">
                                        <button type="submit" name="delete_expense" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors" title="Delete">
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

<?php include '../includes/footer.php'; ?>
